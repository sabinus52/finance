<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Entity\Transaction;
use App\Entity\Vehicle;
use App\Helper\Charts\FuelConsumptionChart;
use App\Helper\Charts\FuelCostByMonthChart;
use App\Helper\Charts\FuelPriceChart;
use App\Helper\Charts\VehicleCostByYearChart;
use App\Helper\Report\VehicleReport;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller des rapports sur les véhicules.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleController extends AbstractController
{
    /**
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Page d'accueil du rapport de la liste de tous les véhicules.
     */
    #[Route(path: '/rapports/vehicules', name: 'report_vehicle__index')]
    public function index(TransactionRepository $repository): Response
    {
        $this->repository = $repository;

        // Liste des anciens véhicules
        $oldQuery = $this->getQueryBase()
            ->andWhere('veh.soldAt IS NOT NULL')
            ->getQuery()
        ;

        // Liste des véhicules en cours d'utilisation
        $currentQuery = $this->getQueryBase()
            ->andWhere('veh.soldAt IS NULL')
            ->getQuery()
        ;

        return $this->render('report/vehicle.html.twig', [
            'vehicles' => [
                'old' => $this->getReportResults($oldQuery),
                'current' => $this->getReportResults($currentQuery),
            ],
        ]);
    }

    /**
     * Page de rapport pour un véhicule.
     */
    #[Route(path: '/rapports/vehicules/{id}', name: 'report_vehicle__item')]
    public function reportByVehicle(Vehicle $vehicle, TransactionRepository $repository): Response
    {
        $transactions = $repository->findAllByVehicle($vehicle);

        $report = new VehicleReport($vehicle, $repository);
        $report->fetchStatistic();

        $consumptionChart = new FuelConsumptionChart();
        $priceChart = new FuelPriceChart();
        $costByMonthChart = new FuelCostByMonthChart();
        $costByYearChart = new VehicleCostByYearChart();

        return $this->render('report/vehicle-item.html.twig', [
            'vehicle' => $vehicle,
            'report' => $report,
            'transactions' => $transactions,
            'chart' => [
                'consumption' => $consumptionChart->getChart([$transactions, $report, $vehicle]),
                'price' => $priceChart->getChart([$transactions, $report]),
                'costbymonth' => $costByMonthChart->getChart([$transactions, $report, $vehicle]),
                'costbyyear' => $costByYearChart->getChart([$transactions, $vehicle]),
            ],
        ]);
    }

    /**
     * Retourne le résumé du rapport des coûts par véhicule.
     *
     * @return array<mixed>
     */
    private function getReportResults(Query $query): array
    {
        $result = [];
        foreach ($query->getResult() as $item) {
            /** @var Transaction */
            $transaction = $item[0];
            $report = new VehicleReport($transaction->getTransactionVehicle()->getVehicle(), $this->repository);
            $report
                ->setMileAge((int) $item['mileage'])
                ->setTotalCost((float) $item['totalCost'])
                ->setTotalVolume((float) $item['totalVolume'])
            ;
            $result[] = $report;
        }

        return $result;
    }

    /**
     * Retourne la requête de base des stats des coûts.
     *
     * @return QueryBuilder
     */
    private function getQueryBase(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('t')
            ->addSelect('SUM(t.amount) AS totalCost')
            ->addSelect('AVG(t.amount) AS averageCost')
            ->addSelect('MAX(tv.distance) AS mileage')
            ->addSelect('SUM(tv.volume) AS totalVolume')
            ->addSelect('AVG(tv.volume) AS averageVolume')
            ->addSelect('tv')
            ->addSelect('veh')
            ->innerJoin('t.transactionVehicle', 'tv')
            ->innerJoin('tv.vehicle', 'veh')
            ->groupBy('tv.vehicle')
            ->orderBy('veh.boughtAt', 'desc')
        ;
    }
}
