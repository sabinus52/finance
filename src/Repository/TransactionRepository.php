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
use App\Entity\Transaction;
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
     * @param Account      $account
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
            if ('' === $value || null === $value) {
                continue;
            }
            if ('range' === $key) {
                $query->andWhere('trt.date BETWEEN :start AND :end')
                    ->setParameter('start', $value[0])
                    ->setParameter('end', $value[1])
                ;
            } else {
                $query->andWhere(sprintf('trt.%s = :%s', $key, $key))
                    ->setParameter($key, $value)
                ;
            }
        }

        return $query->getQuery()->getResult();
    }
}
