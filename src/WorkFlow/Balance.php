<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Values\AccountType;
use App\Values\TransactionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion de mise à jour du solde des transactions et du compte.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Balance
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionRepository
     */
    private $repository;

    /**
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;

        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $this->repository = $repository;
    }

    /**
     * Mets à jour le solde des transactions après celle définie.
     *
     * @param Transaction $transaction Transaction courante modifiée
     * @param DateTime    $date
     *
     * @return int
     */
    public function updateBalanceAfter(Transaction $transaction, ?DateTime $date = null): int
    {
        // Prends la date la plus ancienne entre celle modifié et avant modification
        if (null === $date) {
            $date = $transaction->getDate();
        } else {
            $date = min($date, $transaction->getDate());
        }

        // Recherche la transaction juste avant celle qui a été ajouté ou modifié.
        $lastTransaction = $this->repository->findOneLastBeforeDate($transaction->getAccount(), $date);
        $balance = $lastTransaction->getBalance();

        $results = $this->repository->findAfterDate($transaction->getAccount(), $date);
        foreach ($results as $item) {
            if (TransactionType::REVALUATION === $item->getType()->getValue()) {
                // Dans le cas d'une valoraisation d'un placement, on doit recalculer le montant et conserver la balance
                $variation = $item->getBalance() - $balance;
                $item->setAmount($variation);
                $item->setCategory($this->getCategory(($variation >= 0.0)));
                $balance = $item->getBalance();
            } else {
                $balance += $item->getAmount();
                $item->setBalance($balance);
            }
        }

        $transaction->getAccount()->setBalance($balance);

        // Dans le cas d'une transaction boursière
        if ($transaction->getTransactionStock()) {
            $this->updateBalanceWallet($transaction->getTransactionStock()->getAccount());
        }

        $this->entityManager->flush();

        return count($results);
    }

    /**
     * Mets à jour le solde de tout le compte défini.
     *
     * @param Account $account
     *
     * @return int
     */
    public function updateBalanceFromScratch(Account $account): int
    {
        if (AccountType::EPARGNE_FINANCIERE === $account->getTypeCode()) {
            $result = $this->updateBalanceWallet($account);
        } else {
            $result = $this->updateBalanceAccount($account);
        }

        $this->entityManager->flush();

        return $result;
    }

    /**
     * Mets à jours tous les comptes bancaires et portefeuilles boursiers.
     *
     * @param bool|null $isOpened
     */
    public function updateAllAccounts(?bool $isOpened = null): void
    {
        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accounts = $repository->findByType(null, $isOpened);

        foreach ($accounts as $account) {
            if (AccountType::EPARGNE_FINANCIERE !== $account->getTypeCode()) {
                $this->updateBalanceWallet($account);
            } else {
                $this->updateBalanceAccount($account);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Met à jour tous les soldes de tous les portefeuilles.
     *
     * @param bool|null $isOpened
     */
    public function updateAllWallets(?bool $isOpened = null): void
    {
        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accounts = $repository->findByType(AccountType::EPARGNE_FINANCIERE, $isOpened);

        foreach ($accounts as $account) {
            $this->updateBalanceWallet($account);
        }

        $this->entityManager->flush();
    }

    /**
     * Retourne les soldes d'un compte à la fin de chaque mois.
     *
     * @param Account $account
     *
     * @return array<float>
     */
    public function getByMonth(Account $account): array
    {
        $results = [];

        // Construit le tableau des soldes par mois
        /** @var Transaction[] $transactions */
        $transactions = $this->entityManager->getRepository(Transaction::class)->findBy(['account' => $account], ['date' => 'ASC', 'id' => 'ASC']);
        foreach ($transactions as $transaction) {
            $results[$transaction->getDate()->format('Y-m')] = round($transaction->getBalance(), 2);
        }

        // Bouche les mois manquants
        $start = clone $account->getOpenedAt();
        $end = $account->getClosedAt();
        $end ??= new DateTime();
        $balance = 0.0;
        while ($start->format('Y-m') <= $end->format('Y-m')) {
            if (!isset($results[$start->format('Y-m')])) {
                $results[$start->format('Y-m')] = $balance;
            }
            $balance = $results[$start->format('Y-m')];
            $start->modify('+ 1 month');
        }
        ksort($results);

        return $results;
    }

    /**
     * Retourne les soldes d'un compte à la fin de chaque année.
     *
     * @param Account $account
     *
     * @return array<float>
     */
    public function getByYear(Account $account): array
    {
        $results = [];

        // Construit le tableau des soldes par an
        /** @var Transaction[] $transactions */
        $transactions = $this->entityManager->getRepository(Transaction::class)->findBy(['account' => $account], ['date' => 'ASC', 'id' => 'ASC']);
        foreach ($transactions as $transaction) {
            $results[$transaction->getDate()->format('Y')] = round($transaction->getBalance(), 2);
        }

        // Bouche les années manquantes
        $start = clone $account->getOpenedAt();
        $end = $account->getClosedAt();
        $end ??= new DateTime();
        $balance = 0.0;
        while ($start->format('Y') <= $end->format('Y')) {
            if (!isset($results[$start->format('Y')])) {
                $results[$start->format('Y')] = $balance;
            }
            $balance = $results[$start->format('Y')];
            $start->modify('+ 1 year');
        }
        ksort($results);

        return $results;
    }

    /**
     * Mets à jour le solde d'un compte standard.
     *
     * @param Account $account
     *
     * @return int
     */
    private function updateBalanceAccount(Account $account): int
    {
        $balance = $account->getInitial();
        $reconcilied = $account->getInitial();
        $invested = $account->getInitial();

        // Liste des transactions par compte
        /** @var Transaction[] $results */
        $results = $this->repository->findAfterDate($account, new DateTime('1970-01-01'));
        foreach ($results as $item) {
            if (TransactionType::REVALUATION === $item->getType()->getValue()) {
                // Dans le cas d'une valoraisation d'un placement, on doit recalculer le montant et conserver la balance
                $variation = $item->getBalance() - $balance;
                $item->setAmount($variation);
                $item->setCategory($this->getCategory(($variation >= 0.0)));
                $balance = $item->getBalance();
            } else {
                $balance += $item->getAmount();
                $item->setBalance($balance);
            }
            if (Transaction::STATE_RECONCILIED === $item->getState()) {
                $reconcilied += $item->getAmount();
            }
            if (Category::INVESTMENT === $item->getCategory()->getCode() && $item->getAmount() > 0) {
                $invested += $item->getAmount();
            }
        }

        $account->setBalance($balance);
        $account->setReconBalance($reconcilied);
        $account->setInvested($invested);

        return count($results);
    }

    /**
     * Mets à jour le solde d'un portefeuille.
     *
     * @param Account $account
     *
     * @return int
     */
    private function updateBalanceWallet(Account $account): int
    {
        $wallet = new Wallet($this->entityManager, $account);

        $wallet->buidAndSaveWallet();
        $walletCurrent = $wallet->getWallet();

        $account->setBalance($walletCurrent->getValorisation());
        if (AccountType::PEA_TITRES === $account->getType()->getValue()) {
            $account->setInvested($account->getAccAssoc()->getInvested());
        } else {
            $account->setInvested($walletCurrent->getAmountInvest());
        }

        return 1;
    }

    /**
     * Retourne la catgorie de Valorisation.
     *
     * @param bool $type
     *
     * @return Category
     */
    private function getCategory(bool $type): Category
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager
            ->getRepository(Category::class)
            ->findOneByCode($type, Category::REVALUATION)
        ;
    }
}
