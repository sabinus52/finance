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
use App\Helper\Report\VehicleReport;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données du prix au litre du carburant.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class FuelPriceChart extends ChartBuilder implements ChartBuilderInterface
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static $defaultOpts = [
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
    private static $defaultData = [
        'label' => null,
        'borderColor' => 'rgba(60,141,188,0.8)',
        'borderWidth' => 1,
        'pointBorderColor' => 'orange',
        'pointBackgroundColor' => 'orange',
        'tension' => 0.3,
        'data' => [],
    ];

    /**
     * Les données de la courbe de la moyenne.
     *
     * @var array<mixed>
     */
    private static $averageData = [
        'label' => null,
        'borderColor' => 'darkorange',
        'borderDash' => [3, 3],
        'pointStyle' => null,
        'pointRadius' => false,
        'borderWidth' => 2,
        'data' => [],
    ];

    public function __construct()
    {
        $this->chart = new Chart(Chart::TYPE_LINE);
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
        $average = $report->getFuelAveragePrice();

        $labels = $values = $colors = [];
        foreach ($transactions as $item) {
            if (null === $item->getTransactionVehicle()->getVolume()) {
                continue;
            }
            // Calcul du prix au litre durant le plein
            $value = round(abs($item->getAmount()) / $item->getTransactionVehicle()->getVolume(), 2);
            $labels[] = $item->getDate()->format('d/m/y');
            $values[] = $value;
            // Point de couleur en fonction de la moyenne
            if ($value > $average * 1.1) {
                $colors[] = 'red';
            } elseif ($value < $average * 0.9) {
                $colors[] = 'green';
            } else {
                $colors[] = 'orange';
            }
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = $values;
        $dataSet['pointBorderColor'] = $colors;
        $dataSet['pointBackgroundColor'] = $colors;

        // Ligne de la moyenne
        $dataSetAvg = self::$averageData;
        $dataSetAvg['data'] = array_pad([], count($values), $average);

        return [
            'labels' => $labels,
            'datasets' => [$dataSet, $dataSetAvg],
        ];
    }
}
