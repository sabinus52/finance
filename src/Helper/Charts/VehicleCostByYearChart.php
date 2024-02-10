<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\Vehicle;
use DateInterval;
use DateTime;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des frais de véhicule par an.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleCostByYearChart extends ChartBuilder implements ChartBuilderInterface
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
        'scales' => [
            'xAxes' => [
                'stacked' => true,
            ],
            'yAxes' => [
                'stacked' => true,
            ],
        ],
        'indexAxis' => 'y',
    ];

    /**
     * Les données par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static $defaultData = [
        'label' => null,
        'axis' => 'y',
        'borderColor' => 'gray',
        'borderWidth' => 1,
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
        /** @var Vehicle $vehicle */
        $vehicle = $datas[1];

        // Initialisation des tableaux de labels et valeurs pour chaque mois
        $fuelValues = $repairValues = $otherValues = [];
        $labels = $this->getArrayByYear(clone $vehicle->getBoughtAt(), $vehicle->getSoldAt());
        foreach ($labels as $year) {
            $fuelValues[$year] = 0;
            $repairValues[$year] = 0;
            $otherValues[$year] = 0;
        }

        foreach ($transactions as $item) {
            $year = $item->getDate()->format('Y');
            // Ne prends pas en compte les transactions futures
            if ($item->getDate()->format('Y-m-d') > date('Y-m-d')) {
                continue;
            }

            switch ($item->getCategory()->getCode()) {
                case Category::CARBURANT:
                    $fuelValues[$year] += abs($item->getAmount());
                    break;
                case Category::VEHICULEREPAIR:
                    $repairValues[$year] += abs($item->getAmount());
                    break;
                case Category::VEHICULEFUNDING:
                    break;
                default:
                    $otherValues[$year] += abs($item->getAmount());
                    break;
            }
        }

        $fuelDataSet = self::$defaultData;
        $fuelDataSet['data'] = array_values($fuelValues);
        $fuelDataSet['backgroundColor'] = 'olivedrab';

        $repairDataSet = self::$defaultData;
        $repairDataSet['data'] = array_values($repairValues);
        $repairDataSet['backgroundColor'] = 'mediumslateblue';

        $otherDataSet = self::$defaultData;
        $otherDataSet['data'] = array_values($otherValues);
        $otherDataSet['backgroundColor'] = 'gray';

        return [
            'labels' => array_values($labels),
            'datasets' => [$fuelDataSet, $repairDataSet, $otherDataSet],
        ];
    }

    /**
     * Retoune le tableau par an dans un intervalle donné.
     *
     * @param DateTime      $dateBegin
     * @param DateTime|null $dateEnd
     *
     * @return array<string>
     */
    private function getArrayByYear(DateTime $dateBegin, ?DateTime $dateEnd): array
    {
        $results = [];
        if (null === $dateEnd) {
            $dateEnd = new DateTime();
        }
        while ($dateBegin->format('Y') <= $dateEnd->format('Y')) {
            $results[$dateBegin->format('Y')] = $dateBegin->format('Y');
            $dateBegin->add(new DateInterval('P1Y')); // Ajoute un an à la date de début
        }

        return $results;
    }
}
