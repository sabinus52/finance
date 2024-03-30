<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Transaction;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Toutes les transactions pour un compte donné.
     *
     * @param array<mixed> $filter
     *
     * @return Transaction[]
     */
    public function findByAccount(Account $account, array $filter = []): array
    {
        $query = $this->createQueryBuilder('trt')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('tsf')
            ->innerJoin('trt.recipient', 'rcp')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->leftJoin('trt.transfer', 'tsf')
            ->leftJoin('tsf.account', 'tac')
            ->andWhere('trt.account = :account')
            ->setParameter('account', $account)
            ->orderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
        ;

        foreach ($filter as $key => $value) {
            // Si null ou vide, pas de filtre
            if ('' === $value) {
                continue;
            }
            if (null === $value) {
                continue;
            }
            if ('range' === $key) {
                $now = new \DateTimeImmutable();
                $query->andWhere('(trt.date BETWEEN :start AND :end OR trt.date BETWEEN :start2 AND :end2)')
                    ->setParameter('start', $value[0])
                    ->setParameter('end', $value[1])
                    ->setParameter('start2', $now->format('Y-m-d'))
                    ->setParameter('end2', $now->modify('+ 30 days')->format('Y-m-d'))
                ;
            } else {
                $query->andWhere(sprintf('trt.%s = :%s', $key, $key))
                    ->setParameter($key, $value)
                ;
            }
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Toutes les transactions pour le rapprochement.
     *
     * @return Transaction[]
     */
    public function findToReconciliation(Account $account): array
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('tsf')
            ->innerJoin('trt.recipient', 'rcp')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->leftJoin('trt.transfer', 'tsf')
            ->leftJoin('tsf.account', 'tac')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.state IN (:state)')
            ->setParameter('account', $account)
            ->setParameter('state', [Transaction::STATE_NONE, Transaction::STATE_RECONTEMP])
            ->orderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recherche la transaction juste avant la date définie.
     */
    public function findOneLastBeforeDate(Account $account, \DateTimeInterface $date): Transaction
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('cat')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date < :date')
            ->setParameter('account', $account)
            ->setParameter('date', $date->format('Y-m-d'))
            ->addOrderBy('trt.date', 'DESC')
            ->addOrderBy('trt.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Recherche les transactions après une date donnée.
     *
     * @return Transaction[]
     */
    public function findAfterDate(Account $account, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('cat')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date >= :date')
            ->setParameter('account', $account)
            ->setParameter('date', $date->format('Y-m-d'))
            ->addOrderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Dernière transaction de valorisation de placement.
     */
    public function findOneLastValorisation(Account $account): ?Transaction
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('cat')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.account = :account')
            ->andWhere('cat.code = :code')
            ->setParameter('account', $account)
            ->setParameter('code', Category::REVALUATION)
            ->orderBy('trt.date', 'DESC')
            ->addOrderBy('trt.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Recherche une transaction de valorisation à une période donnée.
     */
    public function findOneValorisation(Transaction $transaction): ?Transaction
    {
        $query = $this->createQueryBuilder('trt')
            ->addSelect('cat')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.account = :account')
            ->andWhere('cat.code = :code')
            ->andWhere('trt.date = :date')
            ->setParameter('account', $transaction->getAccount())
            ->setParameter('code', Category::REVALUATION)
            ->setParameter('date', $transaction->getDate()->modify('last day of this month')->format('Y-m-d'))
            ->setMaxResults(1)
        ;
        // Ne cherche pas la transaction courante
        if (null !== $transaction->getId()) {
            $query->andWhere('trt.id <> :id')->setParameter('id', $transaction->getId());
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Retourne les transactions d'un véhicule.
     *
     * @return Transaction[]
     */
    public function findAllByVehicle(Vehicle $vehicle): array
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('veh')
            ->innerJoin('trt.recipient', 'rcp')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->innerJoin('trt.transactionVehicle', 'veh')
            ->andWhere('veh.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->orderBy('trt.date', 'ASC')
            ->addOrderBy('veh.distance', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les transactions d'un projet.
     *
     * @return Transaction[]
     */
    public function findAllByProject(Project $project): array
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->innerJoin('trt.recipient', 'rcp')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->andWhere('trt.project = :project')
            ->setParameter('project', $project)
            ->orderBy('trt.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les transactions d'un portefeuille.
     *
     * @return Transaction[]
     */
    public function findAllByWallet(Account $account): array
    {
        return $this->createQueryBuilder('trt')
            ->addSelect('cat')
            ->addSelect('sck')
            ->addSelect('wal')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('trt.transactionStock', 'wal')
            ->innerJoin('wal.stock', 'sck')
            ->andWhere('wal.account = :account')
            ->setParameter('account', $account)
            ->orderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Rapproche toutes les transactions d'un compte.
     */
    public function updateAllReconciliation(Account $account): int
    {
        return $this->createQueryBuilder('trt')
            ->update()
            ->set('trt.state', Transaction::STATE_RECONCILIED)
            ->where('trt.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->execute()
        ;
    }
}
