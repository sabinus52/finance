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
 * Grapique des données de performance glissantes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class PerformanceSlipperyChart extends ChartModel
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
        $performanceColors = [];

        foreach ($datas as $month => $item) {
            $labels[] = $this->getLabel($month);
            $performanceValues[] = round($item->getPerformance() * 100, 2);
            $performanceColors[] = $this->getBackgroundColor($item->getPerformance());
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'backgroundColor' => $performanceColors,
                'data' => $performanceValues,
            ])
        ;
    }

    /**
     * Retourne le label.
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
     */
    private function getBackgroundColor(?float $value): string
    {
        return ($value > 0.0) ? 'green' : 'red';
    }
}
