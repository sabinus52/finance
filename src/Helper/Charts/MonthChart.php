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
 * Grapique des données de performance par mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class MonthChart extends ChartBuilder implements ChartBuilderInterface
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
            'xAxes' => [[
                'gridLines' => [
                    'display' => false,
                ],
            ]],
            'yAxes' => [[
                'gridLines' => [
                    'display' => true,
                ],
            ]],
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
        'pointRadius' => false,
        'pointColor' => '#3b8bba',
        'pointStrokeColor' => 'rgba(60,141,188,1)',
        'pointHighlightFill' => '#fff',
        'pointHighlightStroke' => 'rgba(60,141,188,1)',
        'tension' => 0,
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
        $labels = $values = [];
        /** @var PerfItem[] $datas */
        foreach ($datas as $month => $item) {
            $labels[] = $month;
            $values[] = round($item->getCumulPerf() * 100, 2);
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = $values;

        return [
            'labels' => $labels,
            'datasets' => [$dataSet],
        ];
    }
}
