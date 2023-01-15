<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use App\Helper\PerfItem;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données de performance par année.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class YearChart extends ChartBuilder implements ChartBuilderInterface
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static $defaultOpts = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'datasetFill' => false,
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
        'backgroundColor' => [],
        'borderColor' => 'rgba(60,141,188,0.8)',
        'pointRadius' => false,
        'pointColor' => '#3b8bba',
        'pointStrokeColor' => 'rgba(60,141,188,1)',
        'pointHighlightFill' => '#fff',
        'pointHighlightStroke' => 'rgba(60,141,188,1)',
        'tension' => 0.3,
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
        $labels = $values1 = $values2 = $colors = [];
        /** @var PerfItem[] $datas */
        foreach ($datas as $year => $item) {
            $labels[] = $year;
            $values1[] = round($item->getPerformance() * 100, 2);
            $values2[] = round($item->getCumulPerf() * 100, 2);
            $colors[] = $this->getBackgroundColor($item->getPerformance());
        }

        $dataSet1 = self::$defaultData;
        $dataSet1['data'] = $values1;
        $dataSet1['backgroundColor'] = $colors;
        $dataSet2 = self::$defaultData;
        $dataSet2['data'] = $values2;
        $dataSet2['backgroundColor'] = null;
        $dataSet2['type'] = 'line';

        return [
            'labels' => $labels,
            'datasets' => [$dataSet2, $dataSet1],
        ];
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     *
     * @param float $value
     *
     * @return string
     */
    private function getBackgroundColor(float $value): string
    {
        $result = 'green';
        if ($value < 0.0) {
            $result = 'red';
        } elseif ($value < 0.025) {
            $result = 'orange';
        } elseif ($value < 0.050) {
            $result = 'yellow';
        }

        return $result;
    }
}
