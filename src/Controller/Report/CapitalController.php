<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Charts\PerformanceByYearChart;
use App\Charts\PerformanceCapitalChart;
use App\Charts\PerformanceMonthChart;
use App\Charts\PerformanceSlipperyChart;
use App\Entity\Account;
use App\Entity\Stock;
use App\Helper\PerfItem;
use App\Helper\Performance;
use App\Repository\AccountRepository;
use App\Values\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Controller des rapports des contrats de capitalisation.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CapitalController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PerfItem[]
     */
    private $totalPerfMonth = [];

    /**
     * @var PerfItem[]
     */
    private $totalPerfYear = [];

    /**
     * @var PerfItem[]
     */
    private $totalPerfSlippery = [];

    /**
     * Page de rapport du cumul des contrats de capitalisation.
     */
    #[Route(path: '/rapport/capitalisation', name: 'report_capital')]
    public function index(AccountRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $this->entityManager = $entityManager;
        $perfByAccount = [];

        // Taux d'intérêt
        $indices = $entityManager->getRepository(Stock::class)->findOneBy(['type' => Stock::INTEREST_RATE]);
        $accounts = $repository->findByType(AccountType::EPARGNE_ASSURANCE_VIE, true);
        $total = $this->generatePerformanceTotal($accounts, $indices, $perfByAccount);

        $this->sortAndSetPrevious($this->totalPerfMonth);
        $this->sortAndSetPrevious($this->totalPerfYear);
        $this->totalPerfSlippery = Performance::getPerfSlipperyFromByMonth($this->totalPerfMonth);

        return $this->render('report/capital.html.twig', [
            'total' => [
                'perf' => $total,
                'month' => array_slice($this->totalPerfMonth, -133, 132, true),
                'year' => array_slice($this->totalPerfYear, -12, null, true),
                'slippery' => $this->totalPerfSlippery,
            ],
            'accounts' => $accounts,
            'perfoacc' => $perfByAccount,
            'charts' => [
                'slippery' => $this->getChartBySlippery(),
                'year' => $this->getChartByYear(),
                'month' => $this->getChartByMonth(),
                'capital' => $this->getChartCapital($entityManager),
            ],
        ]);
    }

    /**
     * Retourne le graphique de la performance glissante.
     */
    private function getChartBySlippery(): Chart
    {
        $chart = new PerformanceSlipperyChart();

        return $chart->getChart($this->totalPerfSlippery);
    }

    /**
     * Retourne le graphique de la performance annuelle.
     */
    private function getChartByYear(): Chart
    {
        $chart = new PerformanceByYearChart();

        return $chart->getChart(array_slice($this->totalPerfYear, -12, null, true));
    }

    /**
     * Retourne le graphique de la performance mensuelle.
     */
    private function getChartByMonth(): Chart
    {
        $chart = new PerformanceMonthChart();

        return $chart->getChart(array_slice($this->totalPerfMonth, -133, 132, true));
    }

    /**
     * Retourne le graphique de la performance du capital.
     */
    private function getChartCapital(): Chart
    {
        $chart = new PerformanceCapitalChart();

        return $chart->getChart($this->totalPerfMonth);
    }

    /**
     * Génération de la performance totale cumulés de tous les contrats et par contrat.
     *
     * @param Account[]    $accounts
     * @param array<mixed> $perfByAccount
     */
    private function generatePerformanceTotal(array $accounts, Stock $indices, array &$perfByAccount): Account
    {
        $total = new Account();
        $perfByAccount = [];

        foreach ($accounts as $account) {
            $total->setInvestment($total->getInvestment() + $account->getInvestment());
            $total->setBalance($total->getBalance() + $account->getBalance());
            $perf = new Performance($this->entityManager, $account);

            $byMonth = $perf->getByMonth($indices);
            $this->createTotalPerfItems($byMonth, $this->totalPerfMonth);

            $byYear = $perf->getByYear();
            $this->createTotalPerfItems($byYear, $this->totalPerfYear);

            $slippery = $perf->getBySlippery();

            // Performance par compte
            $perfByAccount[$account->getId()] = [
                'slippery' => $slippery,
                'year' => $byYear,
                'month' => $byMonth,
            ];
        }

        return $total;
    }

    /**
     * Créer les items de performances.
     *
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
                $totalPerfItems[$key]->setIndice($month->getIndice() + $totalPerfItems[$key]->getIndice());
            }
        }
    }

    /**
     * Tri le tableau des performances et attribue la performance précédente pour le calcul par rapport à la période précédente.
     *
     * @param PerfItem[] $totalPerfItems
     */
    private function sortAndSetPrevious(array &$totalPerfItems): void
    {
        ksort($totalPerfItems);
        $previous = null;
        foreach ($totalPerfItems as $perf) {
            if (null !== $previous) {
                $perf->setPrevious($previous);
            }
            $previous = $perf;
        }
    }
}
