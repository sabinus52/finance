<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use App\Entity\Transaction;
use App\Entity\Vehicle;
use App\Helper\Report\VehicleReport;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des frais de carburant par mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class FuelCostByMonthChart extends ChartBuilder implements ChartBuilderInterface
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static array $defaultOpts = [
        'maintainAspectRatio' => false,
        'responsive' => true,
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];

    /**
     * Les données par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static array $defaultData = [
        'label' => null,
        'borderColor' => 'gray',
        'backgroundColor' => [],
        'borderWidth' => 1,
        'data' => [],
    ];

    /**
     * Les données de la courbe de la moyenne.
     *
     * @var array<mixed>
     */
    private static array $averageData = [
        'label' => null,
        'borderColor' => 'darkorange',
        'borderDash' => [3, 3],
        'pointStyle' => null,
        'pointRadius' => false,
        'borderWidth' => 2,
        'type' => Chart::TYPE_LINE,
        'data' => [],
    ];

    public function __construct()
    {
        $this->chart = new Chart(Chart::TYPE_BAR);
    }

    public function getOptions(): array
    {
        return self::$defaultOpts;
    }

    public function getData($datas): array
    {
        /** @var Transaction[] $transactions */
        $transactions = $datas[0];
        /** @var VehicleReport $report */
        $report = $datas[1];
        $average = $report->getFuelCostByMonth();
        /** @var Vehicle $vehicle */
        $vehicle = $datas[2];
        // Initialisation des tableaux de labels et valeurs pour chaque mois
        $values = [];
        $colors = [];
        $labels = $this->getArrayByMonth(clone $vehicle->getBoughtAt(), $vehicle->getSoldAt());
        foreach ($labels as $month) {
            $values[$month] = 0;
            $colors[$month] = 'green';
        }

        foreach ($transactions as $item) {
            if (null === $item->getTransactionVehicle()->getVolume()) {
                continue;
            }
            $month = $item->getDate()->format('m/Y');

            // Incrémente le montant des frais de chaque mois
            if (!isset($values[$month])) {
                $values[$month] = 0;
            }
            $values[$month] += abs($item->getAmount());

            // Point de couleur en fonction de la moyenne
            $colors[$month] = '#fd7e14'; // orange
            if ($values[$month] > $average * 1.3) {
                $colors[$month] = '#dc3545'; // red
            } elseif ($values[$month] < $average * 0.7) {
                $colors[$month] = '#28a745'; // green
            }
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = array_values($values);
        $dataSet['backgroundColor'] = array_values($colors);

        // ligne de la moyenne
        $dataSetAvg = self::$averageData;
        $dataSetAvg['data'] = array_pad([], count($values), $average);

        return [
            'labels' => array_values($labels),
            'datasets' => [$dataSet, $dataSetAvg],
        ];
    }

    /**
     * Retoune le tableau par mois dans un intervalle donné.
     *
     * @param \DateTime|null $dateEnd
     *
     * @return array<string>
     */
    private function getArrayByMonth(\DateTime $dateBegin, ?\DateTime $dateEnd): array
    {
        $results = [];
        if (!$dateEnd instanceof \DateTime) {
            $dateEnd = new \DateTime();
        }
        while ($dateBegin <= $dateEnd) {
            $results[$dateBegin->format('m/Y')] = $dateBegin->format('m/Y');
            $dateBegin->add(new \DateInterval('P1M')); // Ajoute un mois à la date de début
        }

        return $results;
    }
}
