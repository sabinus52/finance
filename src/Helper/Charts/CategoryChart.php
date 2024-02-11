<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique de type camembert pour les catégories.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class CategoryChart extends ChartBuilder implements ChartBuilderInterface
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static array $defaultOpts = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'datasetFill' => false,
        'plugins' => [
            'legend' => [
                'position' => 'right',
                'labels' => [
                    'color' => 'white',
                ],
            ],
        ],
    ];

    /**
     * Les données par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static array $defaultData = [
        'backgroundColor' => ['crimson', 'tomato', 'orange', 'gold', 'greenyellow', 'yellowgreen', 'limegreen'],
        'borderColor' => 'rgba(60,141,188,0.8)',
        'pointRadius' => false,
        'pointColor' => '#3b8bba',
        'pointStrokeColor' => 'rgba(60,141,188,1)',
        'pointHighlightFill' => '#fff',
        'pointHighlightStroke' => 'rgba(60,141,188,1)',
        'hoverOffset' => 8,
        'data' => [],
    ];

    public function __construct()
    {
        $this->chart = new Chart(Chart::TYPE_DOUGHNUT);
    }

    public function getOptions(): array
    {
        return self::$defaultOpts;
    }

    public function getData($datas): array
    {
        $labels = [];
        $values = [];
        foreach (array_slice($datas, 0, 7) as $item) {
            $labels[] = $item['datas']->getFullName();
            $values[] = abs(round($item['total'], 0));
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = $values;

        return [
            'labels' => $labels,
            'datasets' => [$dataSet],
        ];
    }
}
