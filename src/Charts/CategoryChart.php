<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique de type camembert pour les catégories.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class CategoryChart extends ChartModel
{
    public function getType(): string
    {
        return Chart::TYPE_DOUGHNUT;
    }

    public function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'color' => 'white',
                    ],
                ],
            ],
        ];
    }

    public function build(array $datas): void
    {
        $labels = [];
        $values = [];

        foreach (array_slice($datas, 0, 7) as $item) {
            $labels[] = $item['datas']->getFullName();
            $values[] = abs(round($item['total'], 0));
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'backgroundColor' => ['crimson', 'tomato', 'orange', 'gold', 'greenyellow', 'yellowgreen', 'limegreen'],
                'borderColor' => 'rgba(60,141,188,0.8)',
                'data' => $values,
            ])
        ;
    }
}
