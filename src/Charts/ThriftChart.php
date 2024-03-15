<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use App\Helper\Report\ThriftItem;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données de la capacité d'épargne.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ThriftChart extends ChartModel
{
    public function getType(): string
    {
        return Chart::TYPE_BAR;
    }

    public function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
        ];
    }

    /**
     * @param ThriftItem[] $datas
     */
    public function build(array $datas): void
    {
        $labels = [];
        $gabs = [];
        $investments = [];
        $colors = [];

        foreach ($datas as $year => $item) {
            $labels[] = $year;
            $gabs[] = round($item->getDiff());
            $investments[] = round($item->getThrift() + $item->getInvest());
            $colors[] = $this->getBackgroundColor($item->getDiff());
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'label' => 'Différence',
                'backgroundColor' => $colors,
                'data' => $gabs,
            ])
            ->addDataSet([
                'label' => 'Epargne globale',
                'backgroundColor' => 'purple',
                'data' => $investments,
            ])
        ;
    }

    /**
     * Retourne la couleur de chaque barre en fonction de la valeur.
     */
    private function getBackgroundColor(float $value): string
    {
        if ($value < 0.0) {
            return 'red';
        }

        return 'green';
    }
}
