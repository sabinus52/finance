<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\TransactionVehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionVehicle>
 *
 * @method TransactionVehicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionVehicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionVehicle[]    findAll()
 * @method TransactionVehicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionVehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionVehicle::class);
    }
}
