<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Retourne tous les projets avec les complements sur les données de transactions.
     *
     * @return Project[]
     */
    public function findAllComplete(): array
    {
        $query = $this->createQueryBuilder('pjt')
            ->addSelect('COUNT(trt) AS number')
            ->addSelect('SUM(trt.amount) AS total')
            ->leftJoin('pjt.transactions', 'trt')
            ->addGroupBy('pjt.id')
            ->addOrderBy('pjt.finishAt', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    /**
     * Retoune un tableau associatif des projets [Mon projet] => Projet.
     *
     * @return Project[]
     */
    public function get4Import(): array
    {
        $result = [];

        /** @var Project $project */
        foreach ($this->findAll() as $project) {
            $result[$project->getName()] = $project;
        }

        return $result;
    }
}
