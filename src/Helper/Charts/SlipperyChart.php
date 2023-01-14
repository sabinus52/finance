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
 * Grapique des données de performance glissantes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class SlipperyChart extends ChartBuilder
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
        /*'animation' => [
            'duration' => 2000,
            'onComplete' => "function () {
                var chartInstance = this.chart;
                    ctx = chartInstance.ctx;
                ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';

                this.data.datasets.forEach(function (dataset, i) {
                    var meta = chartInstance.controller.getDatasetMeta(i);
                    if (dataset.type != 'line') {
                    meta.data.forEach(function (bar, index) {
                        var data = dataset.data[index];
                        ctx.fillText(data, bar._model.x, bar._model.y - 5);
                    });
                    }
                });",
        ],*/
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
        $dataSet = self::$defaultData;
        $dataSet['data'] = $this->values;
        $dataSet['backgroundColor'] = $this->getBackgroundColor();

        return [
            'labels' => $this->labels,
            'datasets' => [$dataSet],
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

        foreach ($this->values as $value) {
            $result[] = ($value > 0) ? 'green' : 'red';
        }

        return $result;
    }
}
