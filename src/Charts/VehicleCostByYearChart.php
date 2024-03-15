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
use Olix\BackOfficeBundle\Model\ChartModel;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Grapique des données des frais de véhicule par an.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleCostByYearChart extends ChartModel
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
            'scales' => [
                'xAxes' => [
                    'stacked' => true,
                ],
                'yAxes' => [
                    'stacked' => true,
                ],
            ],
            'indexAxis' => 'y',
        ];
    }

    public function build(array $datas): void
    {
        /** @var Transaction[] $transactions */
        $transactions = $datas[0];
        /** @var Vehicle $vehicle */
        $vehicle = $datas[1];

        // Récupération des labels et des données
        $labels = $this->getLabelsByYear($vehicle->getBoughtAt(), $vehicle->getSoldAt());
        $datas = $this->getDatasByYear($transactions, $labels);

        $this
            ->setLabel($labels)
            ->addDataSet([
                'backgroundColor' => 'olivedrab',
                'borderColor' => 'gray',
                'borderWidth' => 1,
                'data' => array_values($datas[0]), // Carburant
            ])
            ->addDataSet([
                'backgroundColor' => 'mediumslateblue',
                'borderColor' => 'gray',
                'borderWidth' => 1,
                'data' => array_values($datas[1]), // Entretion ou réparation
            ])
            ->addDataSet([
                'backgroundColor' => 'gray',
                'borderColor' => 'gray',
                'borderWidth' => 1,
                'data' => array_values($datas[2]), // Autres dépenses
            ])
        ;
    }

    /**
     * Retoune le tableau par an dans un intervalle donné pour les labels.
     *
     * @return array<string>
     */
    private function getLabelsByYear(\DateTimeImmutable $dateBegin, ?\DateTimeImmutable $dateEnd): array
    {
        $results = [];
        if (!$dateEnd instanceof \DateTimeImmutable) {
            $dateEnd = new \DateTimeImmutable();
        }
        while ($dateBegin->format('Y') <= $dateEnd->format('Y')) {
            $results[] = $dateBegin->format('Y');
            $dateBegin = $dateBegin->add(new \DateInterval('P1Y')); // Ajoute un an à la date de début
        }

        return $results;
    }

    /**
     * Retourne les données dans un tableau [carburant, entretien, autres].
     *
     * @param Transaction[] $transactions
     * @param string[]      $labels
     *
     * @return array<mixed>
     */
    private function getDatasByYear(array $transactions, array $labels): array
    {
        $fuelValues = [];
        $repairValues = [];
        $otherValues = [];
        // Initialisation des tableaux de valeurs pour chaque année
        foreach ($labels as $year) {
            $fuelValues[$year] = 0;
            $repairValues[$year] = 0;
            $otherValues[$year] = 0;
        }

        foreach ($transactions as $item) {
            $year = $item->getDate()->format('Y');
            // Ne prends pas en compte les transactions futures
            if ($item->getDate()->format('Y-m-d') > date('Y-m-d')) {
                continue;
            }

            switch ($item->getCategory()->getCode()) {
                case Category::CARBURANT:
                    $fuelValues[$year] += abs($item->getAmount());
                    break;
                case Category::VEHICULEREPAIR:
                    $repairValues[$year] += abs($item->getAmount());
                    break;
                case Category::VEHICULEFUNDING:
                    break;
                default:
                    $otherValues[$year] += abs($item->getAmount());
                    break;
            }
        }

        return [$fuelValues, $repairValues, $otherValues];
    }
}
