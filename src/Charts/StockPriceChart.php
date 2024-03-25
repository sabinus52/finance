<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use App\Entity\StockPrice;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des cotations boursières.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class StockPriceChart extends ChartModel
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

    public function build(array $datas): void
    {
        /** @var StockPrice[] $stockPrices */
        $stockPrices = array_reverse($datas);

        $labels = [];
        $values = [];
        foreach ($stockPrices as $item) {
            $labels[] = $item->getDate()->format('d/m/y');
            $values[] = $item->getPrice();
        }

        $this
            ->setLabel($labels)
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'borderWidth' => 2,
                'pointRadius' => false,
                'tension' => 0.3,
                'data' => $values,
            ])
        ;
    }
}
