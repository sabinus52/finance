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
 * Grapique des données de performance par année.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class YearChart extends ChartBuilder
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
        'legend' => [
            'display' => false,
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
        $dataSet1 = self::$defaultData;
        $dataSet1['data'] = $this->values[0];
        $dataSet1['backgroundColor'] = $this->getBackgroundColor();
        $dataSet2 = self::$defaultData;
        $dataSet2['data'] = $this->values[1];
        $dataSet2['backgroundColor'] = null;
        $dataSet2['type'] = 'line';

        return [
            'labels' => $this->labels,
            'datasets' => [$dataSet2, $dataSet1],
        ];
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     *
     * @return array<string>
     */
    private function getBackgroundColor(): array
    {
        $result = [];

        foreach ($this->values[0] as $value) {
            $temp = 'green';
            if ($value < 0.0) {
                $temp = 'red';
            } elseif ($value < 2.5) {
                $temp = 'orange';
            } elseif ($value < 5.0) {
                $temp = 'yellow';
            }
            $result[] = $temp;
        }

        return $result;
    }
}
