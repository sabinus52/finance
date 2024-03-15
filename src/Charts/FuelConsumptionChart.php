<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Charts;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\Vehicle;
use App\Helper\Report\VehicleReport;
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données de la consommation moyenne de carburant.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class FuelConsumptionChart extends ChartModel
{
    /**
     * @var string[]
     */
    private array $labels = [];

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
        /** @var Transaction[] $transactions */
        $transactions = $datas[0];
        /** @var VehicleReport $vehicleReport */
        $vehicleReport = $datas[1];
        /** @var Vehicle $vehicle */
        $vehicle = $datas[2];

        $this->buildDatas($transactions, $vehicleReport, $vehicle);

        $this
            ->setLabel($this->labels)
            ->addDataSet([
                'borderColor' => 'rgba(60,141,188,0.8)',
                'borderWidth' => 1,
                'pointBorderColor' => $this->colors,
                'pointBackgroundColor' => $this->colors,
                'tension' => 0.3,
                'data' => $this->values, // Courbe par points colorés de la consommation
            ])
            ->addDataSet([
                'borderColor' => 'darkorange',
                'borderDash' => [3, 3],
                'pointStyle' => null,
                'pointRadius' => false,
                'borderWidth' => 2,
                'data' => array_pad([], count($this->values), $vehicleReport->getConsumption()), // Ligne de consommation moyenne totale
            ])
        ;
    }

    /**
     * Traite les données pour le graphique.
     *
     * @param Transaction[] $transactions
     */
    private function buildDatas(array $transactions, VehicleReport $vehicleReport, Vehicle $vehicle): void
    {
        $averageConsumption = $vehicleReport->getConsumption();
        $lastMileAge = $vehicle->getKilometer();

        foreach ($transactions as $item) {
            if (Category::CARBURANT !== $item->getCategory()->getCode()) {
                continue;
            }
            if (null === $item->getTransactionVehicle()->getVolume()) {
                continue;
            }
            // Calcul de la consommation moyenne entre 2 pleins
            $value = round($item->getTransactionVehicle()->getVolume() / ($item->getTransactionVehicle()->getDistance() - $lastMileAge) * 100, 1);
            $this->labels[] = $item->getDate()->format('d/m/y');
            $this->values[] = $value;
            // Point de couleur en fonction de la moyenne
            if ($value > $averageConsumption * 1.1) {
                $this->colors[] = 'red';
            } elseif ($value < $averageConsumption * 0.9) {
                $this->colors[] = 'green';
            } else {
                $this->colors[] = 'orange';
            }
            $lastMileAge = $item->getTransactionVehicle()->getDistance();
        }
    }
}
