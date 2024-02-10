<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\TransactionStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionStock>
 *
 * @method TransactionStock|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionStock|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionStock[]    findAll()
 * @method TransactionStock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionStock::class);
    }
}
