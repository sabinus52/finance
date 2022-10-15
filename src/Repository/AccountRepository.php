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
            $result[$account->getFullName()] = $account;
        }

        return $result;
    }
}
