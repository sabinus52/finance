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
 * Grapique des données de performance par mois du capital et de l'investissement cumulé.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class PerformanceCapitalChart extends ChartModel
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
        $performanceColors = [];
        $investmentValues = [];

        foreach ($datas as $month => $item) {
            if (null === $item->getValuation()) {
                continue;
            }
            $labels[] = $month;
            $performanceValues[] = $item->getValuation();
            $performanceColors[] = $this->getBackgroundColor($item->getCumulPerf());
            $investmentValues[] = $item->getInvestmentCumul();
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'borderColor' => 'gray',
                'borderWidth' => 1,
                'backgroundColor' => $performanceColors,
                'data' => $performanceValues,
            ])
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'pointRadius' => false,
                'borderWidth' => 2,
                'data' => $investmentValues,
            ])
        ;
    }

    /**
     * Retourne la couleur de chaque point en fonction de la valeur.
     */
    private function getBackgroundColor(?float $value): string
    {
        $result = 'green';
        if ($value < 0.0) {
            $result = 'red';
        } elseif ($value < 0.05) {
            $result = 'orange';
        } elseif ($value < 0.1) {
            $result = 'yellow';
        }

        return $result;
    }
}
