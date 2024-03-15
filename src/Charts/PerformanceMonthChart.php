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
 * Grapique des données de performance par mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class PerformanceMonthChart extends ChartModel
{
    public function getType(): string
    {
        return Chart::TYPE_LINE;
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

        foreach ($datas as $month => $item) {
            if (null === $item->getValuation()) {
                continue;
            }
            $labels[] = $month;
            $performanceValues[] = round($item->getCumulPerf() * 100, 2);
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'pointRadius' => false,
                'tension' => 0.3,
                'data' => $performanceValues,
            ])
        ;
    }
}
