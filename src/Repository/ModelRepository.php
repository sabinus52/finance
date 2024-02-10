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
use DateTimeImmutable;
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

    /**
     * Retourne tous les modèles.
     *
     * @return Model[]
     */
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

    /**
     * Retourne les panifications actives.
     *
     * @return Model[]
     */
    public function findScheduleEnabled(): array
    {
        return $this->createQueryBuilder('mod')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('shd')
            ->innerJoin('mod.recipient', 'rcp')
            ->innerJoin('mod.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->innerJoin('mod.schedule', 'shd')
            ->where('shd.state = :state')
            ->setParameter('state', true)
            ->addOrderBy('shd.doAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les panifications à traiter pour la création des transactions.
     *
     * @return Model[]
     */
    public function findScheduleToDo(): array
    {
        $now = new DateTimeImmutable();

        return $this->createQueryBuilder('mod')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('shd')
            ->innerJoin('mod.recipient', 'rcp')
            ->innerJoin('mod.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->innerJoin('mod.schedule', 'shd')
            ->where('shd.state = :state')
            ->andWhere('shd.doAt <= :limit')
            ->setParameter('state', true)
            ->setParameter('limit', $now->modify('+ 10 days')->format('Y-m-d'))
            ->addOrderBy('shd.doAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
