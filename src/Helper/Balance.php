<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
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
     *
     * @return int
     */
    public function updateBalanceAfter(Transaction $transaction): int
    {
        $lastTransaction = $this->findOneLastBefore($transaction);
        $balance = $lastTransaction->getBalance();

        $results = $this->findToDoAfter($transaction);
        foreach ($results as $item) {
            $balance += $item->getAmount();
            $item->setBalance($balance);
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
        $balance = $account->getInitial();
        $invested = $account->getInitial();

        $results = $this->findAll($account);
        foreach ($results as $item) {
            $balance += $item->getAmount();
            $item->setBalance($balance);
            if (Category::VERSEMENT === $item->getCategory()->getCode()) {
            if (Category::INVESTMENT === $item->getCategory()->getCode()) {
                $invested += $item->getAmount();
            }
        }

        $account->setBalance($balance);
        $account->setInvested($invested);
        $this->entityManager->flush();

        return count($results);
    }

    /**
     * Recherche la transaction juste avant celle qui a été ajouté ou modifié.
     *
     * @param Transaction $transaction
     *
     * @return Transaction
     */
    private function findOneLastBefore(Transaction $transaction): Transaction
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date < :date')
            ->setParameter('account', $transaction->getAccount())
            ->setParameter('date', $transaction->getDate()->format('Y-m-d'))
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
     *
     * @return Transaction[]
     */
    private function findToDoAfter(Transaction $transaction): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date >= :date')
            ->setParameter('account', $transaction->getAccount())
            ->setParameter('date', $transaction->getDate()->format('Y-m-d'))
            ->addOrderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recherche toutes les transactions d'un compte.
     *
     * @param Account $account
     *
     * @return Transaction[]
     */
    private function findAll(Account $account): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->addSelect('cat')
            ->from(Transaction::class, 'trt')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.account = :account')
            ->setParameter('account', $account)
            ->addOrderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
