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
use App\Values\AccountType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 *
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * Retourne les comptes ouverts regroupé par type principal pour le menu de la sidebar.
     *
     * @return array<mixed>
     */
    public function findGroupByTypeOpened(): array
    {
        $query = $this->createQueryBuilder('acc')
            ->addSelect('int')
            ->innerJoin('acc.institution', 'int')
            ->andWhere('acc.closedAt IS NULL')
            ->addOrderBy('acc.institution', 'ASC')
            ->addOrderBy('acc.name', 'ASC')
            ->getQuery()
        ;

        $result = [];
        // Initialise les données de types de comptes pour le regroupement
        foreach (AccountType::$valuesGroupBy as $key => $value) {
            $result[$key] = $value;
            $result[$key]['accounts'] = [];
        }

        /** @var Account $account */
        foreach ($query->getResult() as $account) {
            $result[$account->getType()->getTypeCode()]['accounts'][] = $account;
        }

        return $result;
    }

    /**
     * Retourne les comptes d'un type donné.
     *
     * @param int|null  $type
     * @param bool|null $isOpened
     *
     * @return Account[]
     */
    public function findByType(?int $type, ?bool $isOpened): array
    {
        $query = $this->createQueryBuilder('acc')
            ->addSelect('int')
            ->innerJoin('acc.institution', 'int')
            ->addOrderBy('acc.institution', 'ASC')
            ->addOrderBy('acc.name', 'ASC')
        ;

        // Recherche par type
        if (null !== $type) {
            $query->andWhere('acc.type >= :min AND acc.type <= :max')
                ->setParameter('min', $type * 10)
                ->setParameter('max', $type * 10 + 9)
            ;
        }

        // Si ouvert ou fermé
        if (true === $isOpened) {
            $query->andWhere('acc.closedAt IS NULL');
        }
        if (false === $isOpened) {
            $query->andWhere('acc.closedAt IS NOT NULL');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Retoune un tableau associatif des noms des comptes [CA Compte courant] => 1.
     *
     * @return Account[]
     */
    public function get4Import(): array
    {
        $query = $this->createQueryBuilder('acc')
            ->innerJoin('acc.institution', 'int')
            ->getQuery()
        ;
        $result = [];

        /** @var Account $account */
        foreach ($query->getResult() as $account) {
            $result[$account->getName4Import()] = $account;
        }

        return $result;
    }
}
