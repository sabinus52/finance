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
use DateInterval;
use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * Rapport sur les différents frais d'un véhicule.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleReport
{
    /**
     * Repository des transactions.
     *
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Entité du véhicule.
     *
     * @var Vehicle
     */
    private $vehicle;

    /**
     * Coût par catégorie.
     *
     * @var array<float>
     */
    private $cost;

    /**
     * Coût total.
     *
     * @var float
     */
    private $totalCost;

    /**
     * Kilométrage.
     *
     * @var int
     */
    private $mileAge;

    /**
     * Volume de carburant consommé.
     *
     * @var array<float>
     */
    private $volume;

    /**
     * Constructeur.
     *
     * @param Vehicle               $vehicle
     * @param TransactionRepository $repository
     */
    public function __construct(Vehicle $vehicle, TransactionRepository $repository)
    {
        $this->repository = $repository;
        $this->vehicle = $vehicle;
    }

    /**
     * Récupère les statistiques en base.
     */
    public function fetchStatistic(): void
    {
        $result = $this->fetchFuelStatistic();
        $this->cost['fuel'] = (float) ($result['totalCost']);
        $this->mileAge = (int) ($result['mileage']);
        $this->volume['number'] = (float) ($result['number']);
        $this->volume['total'] = (float) ($result['totalVolume']);
        $this->volume['average'] = (float) ($result['averageVolume']);

        $result = $this->fetchRepairStatistic();
        $this->cost['repair'] = (float) ($result['totalCost']);

        $result = $this->fetchFundingStatistic();
        $this->cost['funding'] = (float) ($result['totalCost']);

        $result = $this->fetchOtherStatistic();
        $this->cost['other'] = (float) ($result['totalCost']);

        $this->totalCost = array_sum($this->cost);
    }

    /**
     * Retourne l'entité du véhicule.
     *
     * @return Vehicle
     */
    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    /**
     * Retourne le nombre de jours d'utilisation.
     *
     * @return int
     */
    public function getNumberDays(): int
    {
        $today = ($this->vehicle->getSoldAt()) ?: new DateTime();
        $interval = $today->diff($this->vehicle->getBoughtAt());

        return (int) $interval->days;
    }

    /**
     * Retourne le nombre de mois d'utilisation.
     *
     * @return int
     */
    public function getNumberMonths(): int
    {
        $today = ($this->vehicle->getSoldAt()) ?: new DateTime();
        $interval = $today->diff($this->vehicle->getBoughtAt());

        return (int) $interval->y * 12 + $interval->m;
    }

    /**
     * Retourne l'intervalle de la période d'utilisation.
     *
     * @return DateInterval
     */
    public function getPeriod(): DateInterval
    {
        $today = ($this->vehicle->getSoldAt()) ?: new DateTime();

        return $today->diff($this->vehicle->getBoughtAt());
    }

    /**
     * Kilométrage actuelle.
     *
     * @param int $mileage
     *
     * @return self
     */
    public function setMileAge(int $mileage): self
    {
        $this->mileAge = $mileage;

        return $this;
    }

    /**
     * Retourne le kilométrage actuel.
     *
     * @return int
     */
    public function getMileAge(): int
    {
        return $this->mileAge;
    }

    /**
     * Affecte le volume total de carburant.
     *
     * @param float $volume
     *
     * @return self
     */
    public function setTotalVolume(float $volume): self
    {
        $this->volume['total'] = $volume;

        return $this;
    }

    /**
     * Retourne le volume total.
     *
     * @return float
     */
    public function getTotalVolume(): float
    {
        return $this->volume['total'];
    }

    /**
     * Affecte le coût total du véhicule.
     *
     * @param float $cost
     *
     * @return self
     */
    public function setTotalCost(float $cost): self
    {
        $this->totalCost = $cost;

        return $this;
    }

    /**
     * Retourne le coût total du véhicule.
     *
     * @return float
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
     *
     * @return float
     */
    public function getTotalCostByKm(): float
    {
        return $this->totalCost / ($this->mileAge - $this->vehicle->getKilometer());
    }

    /**
     * Retourne le coût total par jour.
     *
     * @return float
     */
    public function getTotalCostByDay(): float
    {
        return $this->totalCost / $this->getNumberDays();
    }

    /**
     * Retourne le coût total par mois.
     *
     * @return float
     */
    public function getTotalCostByMonth(): float
    {
        return $this->totalCost / $this->getNumberMonths();
    }

    /**
     * Retourne la consommation moyenne.
     *
     * @return float
     */
    public function getConsumption(): float
    {
        return $this->volume['total'] / ($this->mileAge - $this->vehicle->getKilometer()) * 100;
    }

    /**
     * Retourne la distance moyenne entre 2 pleins.
     *
     * @return float
     */
    public function getFuelAverageDistance(): float
    {
        return ($this->mileAge - $this->vehicle->getKilometer()) / $this->volume['number'];
    }

    /**
     * Retourne le volume moyen d'un plein.
     *
     * @return float
     */
    public function getFuelAverageVolume(): float
    {
        return $this->volume['average'];
    }

    /**
     * Retourne le coût moyen d'un plein.
     *
     * @return float
     */
    public function getFuelAverageCost(): float
    {
        return abs($this->cost['fuel']) / $this->volume['number'];
    }

    /**
     * Retourne le prix moyen du carburant au litre.
     *
     * @return float
     */
    public function getFuelAveragePrice(): float
    {
        return abs($this->cost['fuel'] / $this->volume['total']);
    }

    /**
     * Retourne le nombre de plein par mois.
     *
     * @return float
     */
    public function getFuelNumberByMonth(): float
    {
        return $this->volume['total'] / $this->getNumberDays();
    }

    /**
     * Retourne le nombre de plein.
     *
     * @return float
     */
    public function getFuelNumber(): float
    {
        return $this->volume['number'];
    }

    /**
     * Retourne le coût du carburant par kilomètre.
     *
     * @return float
     */
    public function getFuelCostByKm(): float
    {
        return abs($this->cost['fuel']) / ($this->mileAge - $this->vehicle->getKilometer());
    }

    /**
     * Retourne le coût du carburant par jour.
     *
     * @return float
     */
    public function getFuelCostByDay(): float
    {
        return abs($this->cost['fuel']) / $this->getNumberDays();
    }

    /**
     * Retourne le coût du carburant par mois.
     *
     * @return float
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
            ->andWhere('cat.code = :cat')
            ->setParameter('cat', Category::VEHICULEFUNDING)
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
     *
     * @return QueryBuilder
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
