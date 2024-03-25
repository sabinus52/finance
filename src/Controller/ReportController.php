<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Charts\PerformanceByYearChart;
use App\Charts\PerformanceMonthChart;
use App\Charts\PerformanceSlipperyChart;
use App\Charts\ThriftChart;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Helper\PerfItem;
use App\Helper\Performance;
use App\Helper\Report\ThriftCapacity;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Values\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller des rapports.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportController extends AbstractController
{
    #[Route(path: '/rapports/capitalisation', name: 'report_capital')]
    public function indexCapital(AccountRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $total = new Account();
        /** @var PerfItem[] $totalPerfMonth */
        $totalPerfMonth = [];
        /** @var PerfItem[] $totalPerfYear */
        $totalPerfYear = [];
        /** @var PerfItem[] $totalPerfSlippery */
        $totalPerfSlippery = [];

        $perfByAccount = [];
        $accounts = $repository->findByType(AccountType::EPARGNE_ASSURANCE_VIE, true);

        foreach ($accounts as $account) {
            $total->setInvestment($total->getInvestment() + $account->getInvestment());
            $total->setBalance($total->getBalance() + $account->getBalance());
            $perf = new Performance($entityManager, $account);

            $byMonth = $perf->getByMonth();
            $this->createTotalPerfItems($byMonth, $totalPerfMonth);

            $byYear = $perf->getByYear();
            $this->createTotalPerfItems($byYear, $totalPerfYear);

            $slippery = $perf->getBySlippery();
            // $this->createTotalPerfItems($slippery, $totalPerfSlippery);

            $perfByAccount[$account->getId()] = [
                'slippery' => $slippery,
                'year' => $byYear,
                'month' => $byMonth,
            ];
        }

        ksort($totalPerfYear);
        $previous = null;
        foreach ($totalPerfYear as $perf) {
            if (null !== $previous) {
                $perf->setPrevious($previous);
            }

            $previous = $perf;
        }

        ksort($totalPerfMonth);
        $previous = null;
        foreach ($totalPerfMonth as $perf) {
            if (null !== $previous) {
                $perf->setPrevious($previous);
            }

            $previous = $perf;
        }

        $totalPerfSlippery = Performance::getPerfSlipperyFromByMonth($totalPerfMonth);

        $chart2 = new PerformanceMonthChart();
        $chart3 = new PerformanceSlipperyChart();
        $chart4 = new PerformanceByYearChart();

        return $this->render('report/capital.html.twig', [
            'total' => [
                'perf' => $total,
                'month' => $totalPerfMonth,
                'year' => $totalPerfYear,
                'slippery' => $totalPerfSlippery,
            ],
            'accounts' => $accounts,
            'perfoacc' => $perfByAccount,
            'charts' => [
                'slippery' => $chart3->getChart($totalPerfSlippery),
                'year' => $chart4->getChart(array_slice($totalPerfYear, -12, null, true)),
                'month' => $chart2->getChart(array_slice($totalPerfMonth, -133, 132, true)),
            ],
        ]);
    }

    /**
     * @param PerfItem[] $acountPerfItems
     * @param PerfItem[] $totalPerfItems
     */
    private function createTotalPerfItems(array $acountPerfItems, array &$totalPerfItems): void
    {
        foreach ($acountPerfItems as $key => $month) {
            if (!isset($totalPerfItems[$key])) {
                $totalPerfItems[$key] = new PerfItem(Performance::MONTH);
            }
            $period = $month->getPeriod();
            $totalPerfItems[$key]->setPeriod($period);
            $totalPerfItems[$key]->addInvestment($month->getInvestment());
            if ($month->getValuation()) {
                $totalPerfItems[$key]->addValuation($month->getValuation());
            }
        }
    }

    #[Route(path: '/rapports/capacite-epargne', name: 'report_capacity')]
    public function indecCapacity(TransactionRepository $repository): Response
    {
        $now = new \DateTimeImmutable();

        /** @var Transaction[] $transactions */
        $transactions = $repository->createQueryBuilder('trt')
            ->addSelect('acc')
            ->addSelect('trf')
            ->innerJoin('trt.account', 'acc')
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
