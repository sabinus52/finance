<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Créer une nouvelle catégorie.
     *
     * @param bool          $type   Recettes ou dépenses (true|false)
     * @param string        $name   Nom de la catégorie
     * @param Category|null $parent Catégorie de niveu 1
     * @param string|null   $code   Code de la catégorie
     *
     * @return Category
     */
    public function create(bool $type, string $name, Category $parent = null, string $code = null): Category
    {
        $category = new Category();

        // Level créer automatiquement
        $category->setName($name)
            ->setType($type)
            ->setCode($code)
            ->setParent($parent)
        ;
        $this->_em->persist($category);

        return $category;
    }

    /**
     * Retourne les catégories par type de niveau 1.
     *
     * @return Category[]
     */
    public function findLevel1ByType(bool $type): ?array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :val')
            ->setParameter('val', $type)
            ->andWhere('c.level = 1')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne la catégorie en fonction de son code.
     *
     * @param bool $type (recette|depense)
     *
     * @return Category|null
     */
    public function findOneByCode(bool $type, string $code): ?Category
    {
        return $this->createQueryBuilder('cat')
            ->andWhere('cat.type = :val')
            ->setParameter('val', $type)
            ->andWhere('cat.code = :code')
            ->setParameter('code', $code)
            ->andWhere('cat.level = 2')
            ->addOrderBy('cat.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retoune un tableau associatif des catégories de niveau 1 [+/-Level 1] => Category.
     *
     * @return Category[]
     */
    public function get4ImportLevel1(): array
    {
        $query = $this->createQueryBuilder('cat')
            ->andWhere('cat.level = 1')
            ->getQuery()
        ;
        $result = [];

        /** @var Category $category */
        foreach ($query->getResult() as $category) {
            $result[$category->getTypeSymbol().$category->getFullName()] = $category;
        }

        return $result;
    }

    /**
     * Retoune un tableau associatif des catégories de niveau 2
     *  [+/-Level1:Level2] => Category
     *  [+/-code] => Category.
     *
     * @return Category[]
     */
    public function get4ImportLevel2(): array
    {
        $query = $this->createQueryBuilder('cat')
            ->innerJoin('cat.parent', 'par')
            ->andWhere('cat.level = 2')
            ->getQuery()
        ;
        $result = [];

        /** @var Category $category */
        foreach ($query->getResult() as $category) {
            $result[$category->getTypeSymbol().$category->getFullName()] = $category;
            // Association aussi des codes [+/-code] => Category
            if ($category->getCode()) {
                $result[$category->getTypeSymbol().$category->getCode()] = $category;
            }
        }

        return $result;
    }
}
