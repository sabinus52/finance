<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use App\Entity\StockPrice;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des cotations boursières.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class StockPriceChart extends ChartBuilder implements ChartBuilderInterface
{
    /**
     * Les options par défaut du graphique.
     *
     * @var array<mixed>
     */
    private static $defaultOpts = [
        'maintainAspectRatio' => false,
        'responsive' => true,
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
        'label' => null,
        'borderColor' => 'rgba(60,141,188,0.8)',
        'borderWidth' => 2,
        'pointRadius' => false,
        'tension' => 0.3,
        'data' => [],
    ];

    public function __construct()
    {
        $this->chart = new Chart(Chart::TYPE_LINE);
    }

    public function getOptions(): array
    {
        return self::$defaultOpts;
    }

    public function getData($datas): array
    {
        /** @var StockPrice[] $prices */
        $prices = array_reverse($datas);

        $labels = $values = [];
        foreach ($prices as $item) {
            $labels[] = $item->getDate()->format('d/m/y');
            $values[] = $item->getPrice();
        }

        $dataSet = self::$defaultData;
        $dataSet['data'] = $values;

        return [
            'labels' => $labels,
            'datasets' => [$dataSet],
        ];
    }
}
