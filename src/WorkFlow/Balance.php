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
use App\Entity\TransactionStock;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Values\AccountBalance;
use App\Values\AccountType;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion de mise à jour du solde des transactions et du compte.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Balance
{
    /**
     * @var TransactionRepository
     */
    private $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $this->repository = $repository;
    }

    /**
     * Mets à jour le solde des transactions après celle définie.
     *
     * @param Transaction $transaction Transaction courante modifiée
     * @param Transaction $before      Transaction courante mais avant sa modification
     */
    public function updateBalanceAfter(Transaction $transaction, Transaction $before): int
    {
        // Prends la date la plus ancienne entre celle modifié et avant modification
        $date = min($before->getDate(), $transaction->getDate());

        // Recherche la transaction juste avant celle qui a été ajouté ou modifié.
        $lastTransaction = $this->repository->findOneLastBeforeDate($transaction->getAccount(), $date);
        $balance = $lastTransaction->getBalance();

        $results = $this->repository->findAfterDate($transaction->getAccount(), $date);
        foreach ($results as $item) {
            if (TransactionType::REVALUATION === $item->getType()->getValue()) {
                // Dans le cas d'une valoraisation d'un placement, on doit recalculer le montant et conserver la balance
                $variation = $item->getBalance() - $balance;
                $item->setAmount($variation);
                $item->setCategory($this->getCategory($variation >= 0.0));
                $balance = $item->getBalance();
            } else {
                $balance += $item->getAmount();
                $item->setBalance($balance);
            }
        }

        $transaction->getAccount()->setBalance($balance);
        // Recalcul de l'investissement
        if (Category::INVESTMENT === $transaction->getCategory()->getCode() && $transaction->getAmount() >= 0) {
            $invested = $transaction->getAccount()->getInvestment();
            $invested = $invested - $before->getAmount() + $transaction->getAmount();
            $transaction->getAccount()->setInvestment($invested);
        }
        if (Category::REPURCHASE === $transaction->getCategory()->getCode() && $transaction->getAmount() <= 0) {
            $repurchase = $transaction->getAccount()->getRepurchase();
            $repurchase = $repurchase - abs($before->getAmount()) + abs($transaction->getAmount());
            $transaction->getAccount()->setRepurchase($repurchase);
        }

        // Dans le cas d'une transaction boursière
        if ($transaction->getTransactionStock() instanceof TransactionStock) {
            $this->updateBalanceWallet($transaction->getTransactionStock()->getAccount());
        }

        $this->entityManager->flush();

        return count($results);
    }

    /**
     * Mets à jour le solde de tout le compte défini.
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
    public function updateAllAccounts(bool $isOpened = null): void
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
    public function updateAllWallets(bool $isOpened = null): void
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
        $start = $account->getOpenedAt();
        $end = $account->getClosedAt();
        $end ??= new \DateTimeImmutable();
        $balance = 0.0;
        while ($start->format('Y-m') <= $end->format('Y-m')) {
            if (!isset($results[$start->format('Y-m')])) {
                $results[$start->format('Y-m')] = $balance;
            }
            $balance = $results[$start->format('Y-m')];
            $start = $start->modify('+ 1 month');
        }
        ksort($results);

        return $results;
    }

    /**
     * Retourne les soldes d'un compte à la fin de chaque année.
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
        $start = $account->getOpenedAt();
        $end = $account->getClosedAt();
        $end ??= new \DateTimeImmutable();
        $balance = 0.0;
        while ($start->format('Y') <= $end->format('Y')) {
            if (!isset($results[$start->format('Y')])) {
                $results[$start->format('Y')] = $balance;
            }
            $balance = $results[$start->format('Y')];
            $start = $start->modify('+ 1 year');
        }
        ksort($results);

        return $results;
    }

    /**
     * Mets à jour le solde d'un compte standard.
     */
    private function updateBalanceAccount(Account $account): int
    {
        $balance = $account->getInitial();
        $reconcilied = $account->getInitial();
        $investment = 0.0;
        $repurchase = 0.0;

        // Liste des transactions par compte
        /** @var Transaction[] $results */
        $results = $this->repository->findAfterDate($account, new \DateTime('1970-01-01'));
        foreach ($results as $item) {
            if (TransactionType::REVALUATION === $item->getType()->getValue()) {
                // Dans le cas d'une valoraisation d'un placement, on doit recalculer le montant et conserver la balance
                $variation = $item->getBalance() - $balance;
                $item->setAmount($variation);
                $item->setCategory($this->getCategory($variation >= 0.0));
                $balance = $item->getBalance();
            } else {
                $balance += $item->getAmount();
                $item->setBalance($balance);
            }
            if (Transaction::STATE_RECONCILIED === $item->getState()) {
                $reconcilied += $item->getAmount();
            }
            if (Category::INVESTMENT === $item->getCategory()->getCode() && $item->getAmount() > 0) {
                $investment += abs($item->getAmount());
            }
            if (Category::REPURCHASE !== $item->getCategory()->getCode()) {
                continue;
            }
            if ($item->getAmount() >= 0) {
                continue;
            }
            $repurchase += abs($item->getAmount());
        }

        // $metaBalance = new AccountBalance();
        $account->setBalance($balance);
        $account->setReconBalance($reconcilied);
        $account->setInvestment($investment);
        $account->setRepurchase($repurchase);

        return count($results);
    }

    /**
     * Mets à jour le solde d'un portefeuille.
     */
    private function updateBalanceWallet(Account $account): int
    {
        $wallet = new Wallet($this->entityManager, $account);

        $wallet->buidAndSaveWallet();

        $walletCurrent = $wallet->getWallet();

        $account->setBalance($walletCurrent->getValorisation());
        if (AccountType::PEA_TITRES === $account->getType()->getValue()) {
            $account->setInvestment($account->getAccAssoc()->getInvestment());
            $account->setRepurchase($account->getAccAssoc()->getRepurchase());
        } else {
            $account->setInvestment($walletCurrent->getAmountInvest());
        }

        return 1;
    }

    /**
     * Retourne la catgorie de Valorisation.
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
