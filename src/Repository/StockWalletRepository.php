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
use App\Entity\StockWallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockWallet>
 *
 * @method StockWallet|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockWallet|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockWallet[]    findAll()
 * @method StockWallet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockWalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockWallet::class);
    }

    /**
     * Suppression de tout le portefeuille.
     */
    public function removeByAccount(Account $account): void
    {
        $query = $this->createQueryBuilder('wal')
            ->delete()
            ->where('wal.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
        ;
        $query->execute();
    }
}
