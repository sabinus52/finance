<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Charts\ThriftChart;
use App\Entity\Transaction;
use App\Helper\Report\ThriftCapacity;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlleur du rapport du capacité d'épargne.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class CapacityReport extends AbstractController
{
    /**
     * Page d'index.
     */
    #[Route(path: '/rapports/capacite-epargne', name: 'report_capacity')]
    public function index(TransactionRepository $repository): Response
    {
        $now = new \DateTimeImmutable();

        /** @var Transaction[] $transactions */
        $transactions = $repository->createQueryBuilder('trt')
            ->addSelect('acc')
            ->addSelect('trf')
            ->addSelect('cat')
            ->innerJoin('trt.account', 'acc')
            ->innerJoin('trt.category', 'cat')
            ->leftJoin('trt.transfer', 'trf')
            ->andWhere('acc.type < 30')
            ->andWhere('trt.date BETWEEN :start AND :end')
            ->setParameter('start', $now->modify('- 12 years')->modify('first day of this year')->format('Y-m-d'))
            ->setParameter('end', $now->modify('last day of this month')->format('Y-m-d'))
            ->orderBy('trt.date')
            ->getQuery()
            ->getResult()
        ;

        $resultByMonth = [];
        $resultByYear = [];
        foreach ($transactions as $transaction) {
            $unit = $transaction->getAccount()->getUnit();
            if (!array_key_exists($unit, $resultByMonth)) {
                $resultByMonth[$unit] = new ThriftCapacity(ThriftCapacity::BY_MONTH);
            }
            if (!array_key_exists($unit, $resultByYear)) {
                $resultByYear[$unit] = new ThriftCapacity(ThriftCapacity::BY_YEAR);
            }
            $resultByMonth[$unit]->addTransaction($transaction);
            $resultByYear[$unit]->addTransaction($transaction);
        }

        $charts = [];
        foreach ($resultByYear as $unit => $result) {
            $chart = new ThriftChart();
            $charts['year'][$unit] = $chart->getChart($result->getResults());
        }
        foreach ($resultByMonth as $unit => $result) {
            $chart = new ThriftChart();
            $charts['month'][$unit] = $chart->getChart($result->getResults(24));
        }

        return $this->render('report/capacity.html.twig', [
            'results' => [
                'byMonth' => $resultByMonth,
                'byYear' => $resultByYear,
            ],
            'units' => $this->getParameter('app.account.units'),
            'charts' => $charts,
        ]);
    }
}
