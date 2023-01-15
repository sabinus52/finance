<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Account;
use App\Helper\Charts\MonthChart;
use App\Helper\Charts\SlipperyChart;
use App\Helper\Charts\YearChart;
use App\Helper\PerfItem;
use App\Helper\Performance;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Values\AccountType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller des rapports.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ReportController extends AbstractController
{
    /**
     * @Route("/rapports/capitalisation", name="report_capital")
     */
    public function index(AccountRepository $repository, TransactionRepository $repoTransac): Response
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
            $total->setInvested($total->getInvested() + $account->getInvested());
            $total->setBalance($total->getBalance() + $account->getBalance());
            $perf = new Performance($repoTransac, $account);

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
        foreach ($totalPerfYear as $key => $perf) {
            if (null !== $previous) {
                $perf->setPrevious($previous);
            }

            $previous = $perf;
        }

        ksort($totalPerfMonth);
        $previous = null;
        foreach ($totalPerfMonth as $key => $perf) {
            if (null !== $previous) {
                $perf->setPrevious($previous);
            }

            $previous = $perf;
        }

        $totalPerfSlippery = Performance::getPerfSlipperyFromByMonth($totalPerfMonth);

        $chart2 = new MonthChart();
        $chart3 = new SlipperyChart();
        $chart4 = new YearChart();

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
                'year' => $chart4->getChart($totalPerfYear),
                'month' => $chart2->getChart($totalPerfMonth),
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
            $period = DateTime::createFromImmutable($month->getPeriod());
            $totalPerfItems[$key]->setPeriod($period);
            $totalPerfItems[$key]->addInvest($month->getInvestCumul());
            $totalPerfItems[$key]->addValuation($month->getValuation());
        }
    }
}
