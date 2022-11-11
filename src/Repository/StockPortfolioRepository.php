<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\StockPortfolio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockPortfolio>
 *
 * @method StockPortfolio|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockPortfolio|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockPortfolio[]    findAll()
 * @method StockPortfolio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockPortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockPortfolio::class);
    }
}
