<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use App\Helper\PerfItem;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données de performance par année.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class PerformanceByYearChart extends ChartModel
{
    public function getType(): string
    {
        return Chart::TYPE_BAR;
    }

    public function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    /**
     * @param PerfItem[] $datas
     */
    public function build(array $datas): void
    {
        $labels = [];
        $performanceValues = [];
        $cumulPerfValues = [];
        $performanceColors = [];

        foreach ($datas as $year => $item) {
            if (null === $item->getValuation()) {
                continue;
            }
            $labels[] = $year;
            $performanceValues[] = round($item->getPerformance() * 100, 2);
            $cumulPerfValues[] = round($item->getCumulPerf() * 100, 2);
            $performanceColors[] = $this->getBackgroundColor($item->getPerformance());
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'backgroundColor' => $performanceColors,
                'data' => $performanceValues,
            ])
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'borderWidth' => 2,
                'pointRadius' => false,
                'tension' => 0.3,
                'type' => 'line',
                'data' => $cumulPerfValues,
            ])
        ;
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     */
    private function getBackgroundColor(?float $value): string
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
