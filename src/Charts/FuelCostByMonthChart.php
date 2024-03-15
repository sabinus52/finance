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
use App\Entity\Vehicle;
use App\Helper\Report\VehicleReport;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des frais de carburant par mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class FuelCostByMonthChart extends ChartModel
{
    /**
     * @var string[]
     */
    private array $colors = [];

    /**
     * @var float[]
     */
    private array $values = [];

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

    public function build(array $datas): void
    {
        /** @var Transaction[] $transactions */
        $transactions = $datas[0];
        /** @var VehicleReport $vehicleReport */
        $vehicleReport = $datas[1];
        /** @var Vehicle $vehicle */
        $vehicle = $datas[2];

        $labels = $this->getLabelsByMonth($vehicle->getBoughtAt(), $vehicle->getSoldAt());
        foreach ($labels as $month) {
            $this->values[$month] = 0;
            $this->colors[$month] = 'green';
        }

        $this->buildDatas($transactions, $vehicleReport);

        $this
            ->setLabel($labels)
            ->addDataSet([
                'borderColor' => 'gray',
                'borderWidth' => 1,
                'backgroundColor' => array_values($this->colors),
                'data' => array_values($this->values), // Courbe par points colorés du prix/litre
            ])
            ->addDataSet([
                'borderColor' => 'darkorange',
                'borderDash' => [3, 3],
                'pointStyle' => null,
                'pointRadius' => false,
                'borderWidth' => 2,
                'type' => Chart::TYPE_LINE,
                'data' => array_pad([], count($this->values), $vehicleReport->getFuelCostByMonth()), // Ligne de la moyenne du prix sur toute la durée
            ])
        ;
    }

    /**
     * Traite les données pour le graphique.
     *
     * @param Transaction[] $transactions
     */
    private function buildDatas(array $transactions, VehicleReport $vehicleReport): void
    {
        $averageFuelCost = $vehicleReport->getFuelCostByMonth();

        foreach ($transactions as $item) {
            if (null === $item->getTransactionVehicle()->getVolume()) {
                continue;
            }
            $month = $item->getDate()->format('m/Y');

            // Incrémente le montant des frais de chaque mois
            if (!isset($this->values[$month])) {
                $this->values[$month] = 0;
            }
            $this->values[$month] += abs($item->getAmount());

            // Point de couleur en fonction de la moyenne
            $this->colors[$month] = '#fd7e14'; // orange
            if ($this->values[$month] > $averageFuelCost * 1.3) {
                $this->colors[$month] = '#dc3545'; // red
            } elseif ($this->values[$month] < $averageFuelCost * 0.7) {
                $this->colors[$month] = '#28a745'; // green
            }
        }
    }

    /**
     * Retoune le tableau par mois dans un intervalle donné.
     *
     * @return array<string>
     */
    private function getLabelsByMonth(\DateTimeImmutable $dateBegin, ?\DateTimeImmutable $dateEnd): array
    {
        $results = [];
        if (!$dateEnd instanceof \DateTimeImmutable) {
            $dateEnd = new \DateTimeImmutable();
        }
        while ($dateBegin <= $dateEnd) {
            $results[] = $dateBegin->format('m/Y');
            $dateBegin = $dateBegin->add(new \DateInterval('P1M')); // Ajoute un mois à la date de début
        }

        return $results;
    }
}
