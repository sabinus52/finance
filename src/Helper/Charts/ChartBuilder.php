<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Charts;

use Symfony\UX\Chartjs\Model\Chart;

/**
 * Construction des graphiques.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
abstract class ChartBuilder implements ChartBuilderInterface
{
    /**
     * @var Chart
     */
    protected $chart;

    /**
     * Retourne le graphique avec les données.
     */
    public function getChart(mixed $data): Chart
    {
        $this->chart->setOptions($this->getOptions());
        $this->chart->setData($this->getData($data));

        return $this->chart;
    }
}
