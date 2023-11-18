<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Stock;
use App\Entity\StockPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockPrice>
 *
 * @method StockPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockPrice[]    findAll()
 * @method StockPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockPrice::class);
    }

    /**
     * Retourne les prices des titres par titres et date.
     *
     * @return array<array<float>>
     */
    public function findGroupByStockDate(): array
    {
        $results = [];
        $prices = $this->findAll();
        foreach ($prices as $price) {
            $results[$price->getStock()->getId()][$price->getDate()->format('Y-m-d')] = $price->getPrice();
        }

        return $results;
    }

    /**
     * Dernière cotation boursière.
     *
     * @param Stock $stock
     *
     * @return StockPrice|null
     */
    public function findOneLastPrice(Stock $stock): ?StockPrice
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.stock = :stock')
            ->setParameter('stock', $stock)
            ->orderBy('sp.date', 'DESC')
            ->addOrderBy('sp.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
