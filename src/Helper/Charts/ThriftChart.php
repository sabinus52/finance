<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use App\Helper\Report\ThriftItem;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données de la capacité d'épargne.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ThriftChart extends ChartBuilder implements ChartBuilderInterface
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
                'display' => true,
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
        'label' => '',
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
        /** @var ThriftItem[] $datas */
        foreach ($datas as $year => $item) {
            $labels[] = $year;
            $values1[] = round($item->getDiff());
            $values2[] = round($item->getThrift() + $item->getInvest());
            $colors[] = $this->getBackgroundColor($item->getDiff());
        }

        $dataSet1 = self::$defaultData;
        $dataSet1['label'] = 'Différence';
        $dataSet1['data'] = $values1;
        $dataSet1['backgroundColor'] = $colors;
        $dataSet2 = self::$defaultData;
        $dataSet2['label'] = 'Epargne globale';
        $dataSet2['data'] = $values2;
        $dataSet2['backgroundColor'] = 'purple';

        return [
            'labels' => $labels,
            'datasets' => [$dataSet1, $dataSet2],
        ];
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     *
     * @return string
     */
    private function getBackgroundColor(float $value): string
    {
        $result = 'green';
        if ($value < 0.0) {
            $result = 'red';
        }

        return $result;
    }
}
