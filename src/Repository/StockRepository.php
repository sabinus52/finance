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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 *
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * Retourne la liste des cotations et indices boursiers avec sa dernières valeurs.
     *
     * @return Stock[]
     */
    public function findAllWhithLastPrice(): array
    {
        // Liste des cotations avec la dernières valeurs
        $dql = "SELECT stock, prices
        FROM App\\Entity\\Stock stock
        JOIN stock.stockPrices prices
        WHERE CONCAT(stock.id, ' ', prices.date) IN (
            SELECT CONCAT(stock2.id, ' ', MAX(prices2.date))
            FROM App\\Entity\\Stock stock2
            JOIN stock2.stockPrices prices2
            GROUP BY stock2.id
        )";
        $query = $this->_em->createQuery($dql);
        $result = $query->getResult();

        // Liste des cotations sans valeurs
        $dql = 'SELECT stock, prices
        FROM App\Entity\Stock stock
        LEFT JOIN stock.stockPrices prices
        GROUP BY stock
        HAVING COUNT(prices) = 0';
        $query = $this->_em->createQuery($dql);

        return array_merge($result, $query->getResult());
    }

    /**
     * Retoune un tableau associatif des titres [Crédit Agricole SA] => Stock.
     *
     * @return Stock[]
     */
    public function get4Import(): array
    {
        $result = [];

        /** @var Stock $stock */
        foreach ($this->findAll() as $stock) {
            $result[$stock->getName()] = $stock;
        }

        return $result;
    }
}
