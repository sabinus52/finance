<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use App\Entity\Transaction;
use App\Helper\DateRange;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique de l'évolution du solde d'un compte par jour.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class BalanceDayChart extends ChartModel
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
     * @param Transaction[] $datas
     */
    public function build(array $datas): void
    {
        $dateRange = new DateRange(DateRange::LAST_90D);
        $labels = $dateRange->getDaysInRange();
        $dateRange = new DateRange(DateRange::NEXT_60D);
        $labels += $dateRange->getDaysInRange();
        $amountByDate = [];
        $borderColor = [];
        $pointRadius = [];

        /** @var Transaction[] $datas */
        foreach ($datas as $transaction) {
            // Calcule le montant dépensé ou recetté pour chaque jour trouvé
            $date = $transaction->getDate()->format('Y-m-d');
            if (!array_key_exists($date, $amountByDate)) {
                $amountByDate[$date] = 0;
            }
            $amountByDate[$date] += $transaction->getAmount();
        }

        $balance = (array_key_exists(0, $datas)) ? $datas[0]->getBalance() : 0;
        $balanceByDate = [];
        foreach ($labels as $label) {
            // Calcule la balance pour tous les jours du graphique
            if (array_key_exists($label, $amountByDate)) {
                $balance += $amountByDate[$label];
            }
            $balanceByDate[$label] = $balance;

            // Point pour la date du jour et chaque début de mois
            if ('01' === substr($label, -2, 2)) {
                $pointRadius[] = 3;
                $borderColor[] = 'gray';
            } elseif ($label === (new \DateTimeImmutable())->format('Y-m-d')) {
                $pointRadius[] = 5;
                $borderColor[] = 'red';
            } else {
                $pointRadius[] = 0;
                $borderColor[] = 'gray';
            }
        }

        $this
            ->setLabel(array_values($labels))
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'borderWidth' => 1,
                'pointRadius' => $pointRadius,
                'pointBorderColor' => array_values($borderColor),
                'pointBackgroundColor' => array_values($borderColor),
                'tension' => 0.3,
                'data' => array_values($balanceByDate),
            ])
        ;
    }
}
