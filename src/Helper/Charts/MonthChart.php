<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

/**
 * Grapique des données de performance par mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class MonthChart extends ChartBuilder
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static $defaultOpts = [
        'maintainAspectRatio' => false,
        'responsive' => true,
        'legend' => [
            'display' => false,
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
        'borderColor' => 'rgba(60,141,188,0.8)',
        'pointRadius' => false,
        'pointColor' => '#3b8bba',
        'pointStrokeColor' => 'rgba(60,141,188,1)',
        'pointHighlightFill' => '#fff',
        'pointHighlightStroke' => 'rgba(60,141,188,1)',
        'data' => [],
    ];

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->setOptions(self::$defaultOpts);
    }

    /**
     * Retourne les données du graphique.
     *
     * @return array<mixed>
     */
    public function getData(): array
    {
        $dataSet = self::$defaultData;
        $dataSet['data'] = $this->values;

        return [
            'labels' => $this->labels,
            'datasets' => [$dataSet],
        ];
    }
}
