<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Report;

use App\Entity\Category;
use App\Entity\Vehicle;
use App\Repository\TransactionRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Rapport sur les différents frais d'un véhicule.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleReport
{
    /**
     * Coût par catégorie.
     *
     * @var array<float>
     */
    private ?array $cost = null;

    /**
     * Coût total.
     */
    private ?float $totalCost = null;

    /**
     * Kilométrage.
     */
    private ?int $mileAge = null;

    /**
     * Volume de carburant consommé.
     *
     * @var array<float>
     */
    private ?array $volume = null;

    /**
     * Constructeur.
     */
    public function __construct(private readonly Vehicle $vehicle, private readonly TransactionRepository $repository)
    {
    }

    /**
     * Récupère les statistiques en base.
     */
    public function fetchStatistic(): void
    {
        $result = $this->fetchFuelStatistic();
        $this->cost['fuel'] = $result['totalCost'] ?: 0;
        $this->mileAge = (int) $result['mileage'];
        $this->volume['number'] = $result['number'] ?: 0;
        $this->volume['total'] = $result['totalVolume'] ?: 0;
        $this->volume['average'] = $result['averageVolume'] ?: 0;

        $result = $this->fetchRepairStatistic();
        $this->cost['repair'] = $result['totalCost'] ?: 0;

        $result = $this->fetchFundingStatistic();
        $this->cost['funding'] = $result['totalCost'] ?: 0;

        $result = $this->fetchOtherStatistic();
        $this->cost['other'] = $result['totalCost'] ?: 0;

        $this->totalCost = array_sum($this->cost);
    }

    /**
     * Retourne l'entité du véhicule.
     */
    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    /**
     * Retourne le nombre de jours d'utilisation.
     */
    public function getNumberDays(): int
    {
        $today = ($this->vehicle->getSoldAt() instanceof \DateTimeImmutable) ? $this->vehicle->getSoldAt() : new \DateTimeImmutable();
        $interval = $today->diff($this->getFirstCirculationAt());

        if (0 === $interval->days) {
            return 1;
        }

        return (int) $interval->days;
    }

    /**
     * Retourne le nombre de mois d'utilisation.
     */
    public function getNumberMonths(): int
    {
        $today = ($this->vehicle->getSoldAt() instanceof \DateTimeImmutable) ? $this->vehicle->getSoldAt() : new \DateTimeImmutable();
        $interval = $today->diff($this->getFirstCirculationAt());
        $months = (int) $interval->y * 12 + $interval->m;

        if (0 === $months) {
            return 1;
        }

        return $months;
    }

    /**
     * Retourne l'intervalle de la période d'utilisation.
     */
    public function getPeriod(): \DateInterval
    {
        $today = ($this->vehicle->getSoldAt() instanceof \DateTimeImmutable) ? $this->vehicle->getSoldAt() : new \DateTimeImmutable();

        return $today->diff($this->getFirstCirculationAt());
    }

    /**
     * Retoune la date de la ère curculation.
     */
    public function getFirstCirculationAt(): \DateTimeImmutable
    {
        if ($this->vehicle->getRegisteredAt() instanceof \DateTimeImmutable) {
            return $this->vehicle->getRegisteredAt();
        }

        return $this->vehicle->getBoughtAt();
    }

    /**
     * Kilométrage actuelle.
     */
    public function setMileAge(int $mileage): self
    {
        $this->mileAge = $mileage;

        return $this;
    }

    /**
     * Retourne le kilométrage actuel.
     */
    public function getMileAge(): int
    {
        return $this->mileAge;
    }

    /**
     * Retourne la distance parcourue.
     */
    public function getDistance(): int
    {
        $distance = $this->getMileAge() - $this->vehicle->getKilometer();
        if (0 === $distance) {
            return 1;
        }

        return $distance;
    }

    /**
     * Affecte le volume total de carburant.
     */
    public function setTotalVolume(float $volume): self
    {
        $this->volume['total'] = $volume;

        return $this;
    }

    /**
     * Retourne le volume total.
     */
    public function getTotalVolume(): float
    {
        return $this->volume['total'];
    }

    /**
     * Affecte le coût total du véhicule.
     */
    public function setTotalCost(float $cost): self
    {
        $this->totalCost = $cost;

        return $this;
    }

    /**
     * Retourne le coût total du véhicule.
     */
    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    /**
     * Retounne les coût par catégorie.
     *
     * @return array<float>
     */
    public function getCost(): array
    {
        return $this->cost;
    }

    /**
     * Retourne le coû total par kilomètre.
     */
    public function getTotalCostByKm(): float
    {
        return $this->totalCost / $this->getDistance();
    }

    /**
     * Retourne le coût total par jour.
     */
    public function getTotalCostByDay(): float
    {
        return $this->totalCost / $this->getNumberDays();
    }

    /**
     * Retourne le coût total par mois.
     */
    public function getTotalCostByMonth(): float
    {
        return $this->totalCost / $this->getNumberMonths();
    }

    /**
     * Retourne la consommation moyenne.
     */
    public function getConsumption(): float
    {
        return $this->volume['total'] / $this->getDistance() * 100;
    }

    /**
     * Retourne la distance moyenne entre 2 pleins.
     */
    public function getFuelAverageDistance(): float
    {
        if (0 === (int) $this->volume['number']) {
            return 0;
        }

        return $this->getDistance() / $this->volume['number'];
    }

    /**
     * Retourne le volume moyen d'un plein.
     */
    public function getFuelAverageVolume(): float
    {
        return $this->volume['average'];
    }

    /**
     * Retourne le coût moyen d'un plein.
     */
    public function getFuelAverageCost(): float
    {
        if (0 === (int) $this->volume['number']) {
            return 0;
        }

        return abs($this->cost['fuel']) / $this->volume['number'];
    }

    /**
     * Retourne le prix moyen du carburant au litre.
     */
    public function getFuelAveragePrice(): float
    {
        if (0 === (int) $this->volume['total']) {
            return 0;
        }

        return abs($this->cost['fuel'] / $this->volume['total']);
    }

    /**
     * Retourne le nombre de plein par mois.
     */
    public function getFuelNumberByMonth(): float
    {
        return $this->volume['total'] / $this->getNumberDays();
    }

    /**
     * Retourne le nombre de plein.
     */
    public function getFuelNumber(): float
    {
        return $this->volume['number'];
    }

    /**
     * Retourne le coût du carburant par kilomètre.
     */
    public function getFuelCostByKm(): float
    {
        return abs($this->cost['fuel']) / $this->getDistance();
    }

    /**
     * Retourne le coût du carburant par jour.
     */
    public function getFuelCostByDay(): float
    {
        return abs($this->cost['fuel']) / $this->getNumberDays();
    }

    /**
     * Retourne le coût du carburant par mois.
     */
    public function getFuelCostByMonth(): float
    {
        return abs($this->cost['fuel']) / $this->getNumberMonths();
    }

    /**
     * Retoune les stats des données des transactions du caarburant.
     *
     * @return array<float>
     */
    private function fetchFuelStatistic(): array
    {
        return $this->getQueryBase()
            ->addSelect('MAX(tv.distance) AS mileage')
            ->addSelect('SUM(tv.volume) AS totalVolume')
            ->addSelect('AVG(tv.volume) AS averageVolume')
            ->andWhere('cat.code = :cat')
            ->setParameter('cat', Category::CARBURANT)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retoune les stats des données du financement / revente du véhicule.
     *
     * @return array<float>
     */
    private function fetchFundingStatistic(): array
    {
        return $this->getQueryBase()
            ->andWhere('cat.code IN (:cat)')
            ->setParameter('cat', [Category::VEHICULEFUNDING, Category::RESALE])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retoune les stats des données des transactions d'entretien.
     *
     * @return array<float>
     */
    private function fetchRepairStatistic(): array
    {
        return $this->getQueryBase()
            ->andWhere('cat.code = :cat')
            ->setParameter('cat', Category::VEHICULEREPAIR)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retoune les stats des données des transactions d'autres dépenses.
     *
     * @return array<float>
     */
    private function fetchOtherStatistic(): array
    {
        return $this->getQueryBase()
            ->andWhere('cat.code IS NULL')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Requête de base.
     */
    private function getQueryBase(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('t')
            ->select('COUNT(t.id) AS number')
            ->addSelect('SUM(t.amount) AS totalCost')
            ->addSelect('AVG(t.amount) AS averageCost')
            ->innerJoin('t.transactionVehicle', 'tv')
            ->innerJoin('t.category', 'cat')
            ->where('tv.vehicle = :vehicle')
            ->setParameter('vehicle', $this->vehicle)
        ;
    }
}
