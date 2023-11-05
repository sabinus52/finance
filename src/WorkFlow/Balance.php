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
use App\Entity\StockWallet;
use App\Entity\Transaction;
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
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
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

        $lastTransaction = $this->findOneLastBefore($transaction, $date);
        $balance = $lastTransaction->getBalance();

        $results = $this->findToDoAfter($transaction, $date);
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
    public function updateBalanceAll(Account $account): int
    {
        if (AccountType::EPARGNE_FINANCIERE === $account->getTypeCode()) {
            return $this->updateBalanceAllWallet($account);
        }

        return $this->updateBalanceAllAccount($account);
    }

    /**
     * Mets à jour le solde d'un compte standard.
     *
     * @param Account $account
     *
     * @return int
     */
    private function updateBalanceAllAccount(Account $account): int
    {
        $balance = $account->getInitial();
        $reconcilied = $account->getInitial();
        $invested = $account->getInitial();

        // Liste des transactions par compte
        $results = $this->entityManager->getRepository(Transaction::class)->findBy(['account' => $account], ['date' => 'ASC', 'id' => 'ASC']);
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
        $this->entityManager->flush();

        return count($results);
    }

    /**
     * Mets à jour le solde d'un portefeuille.
     *
     * @param Account $account
     *
     * @return int
     */
    private function updateBalanceAllWallet(Account $account): int
    {
        $balance = 0.0;
        /** @var StockWallet[] $results */
        $results = $this->entityManager->getRepository(StockWallet::class)->findByAccount($account); /* @phpstan-ignore-line */
        foreach ($results as $item) {
            $balance += $item->getVolume() * $item->getPrice();
        }

        $account->setBalance(round($balance, 2));
        // $account->setInvested($invested); TODO à rajouter avec un nouveau champs
        $this->entityManager->flush();

        return count($results);
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

    /**
     * Recherche la transaction juste avant celle qui a été ajouté ou modifié.
     *
     * @param Transaction $transaction
     * @param DateTime    $date
     *
     * @return Transaction
     */
    private function findOneLastBefore(Transaction $transaction, DateTime $date): Transaction
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date < :date')
            ->setParameter('account', $transaction->getAccount())
            ->setParameter('date', $date->format('Y-m-d'))
            ->addOrderBy('trt.date', 'DESC')
            ->addOrderBy('trt.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Recherche les transactions à mettre à jour à partir de la date de modif.
     *
     * @param Transaction $transaction
     * @param DateTime    $date
     *
     * @return Transaction[]
     */
    private function findToDoAfter(Transaction $transaction, DateTime $date): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date >= :date')
            ->setParameter('account', $transaction->getAccount())
            ->setParameter('date', $date->format('Y-m-d'))
            ->addOrderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
