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
 * Grapique des données de performance glissantes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class SlipperyChart extends ChartBuilder implements ChartBuilderInterface
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
        $labels = $values = $colors = [];
        /** @var PerfItem[] $datas */
        foreach ($datas as $month => $item) {
            $labels[] = $this->getLabel($month);
            $values[] = round($item->getPerformance() * 100, 2);
            $colors[] = $this->getBackgroundColor($item->getPerformance());
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = $values;
        $dataSet['backgroundColor'] = $colors;

        return [
            'labels' => $labels,
            'datasets' => [$dataSet],
        ];
    }

    /**
     * Retourne le label.
     *
     * @return string
     */
    public function getLabel(int $month): string
    {
        if (12 === $month) {
            return '1 an';
        }
        if ($month < 12) {
            return sprintf('%s mois', $month);
        }

        return sprintf('%s ans', $month / 12);
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     *
     * @param float $value
     *
     * @return string
     */
    private function getBackgroundColor(?float $value): string
    {
        return ($value > 0.0) ? 'green' : 'red';
    }
}
