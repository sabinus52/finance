<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Model>
 *
 * @method Model|null find($id, $lockMode = null, $lockVersion = null)
 * @method Model|null findOneBy(array $criteria, array $orderBy = null)
 * @method Model[]    findAll()
 * @method Model[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('mod')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->innerJoin('mod.recipient', 'rcp')
            ->innerJoin('mod.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->addOrderBy('mod.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
