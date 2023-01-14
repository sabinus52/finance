<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use DateTime;
use DateTimeImmutable;

/**
 * Element d'une période (mois, trimestre, année) de performance.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class PerfItem
{
    /**
     * Type de la période (Performance::MONTH).
     *
     * @var int
     */
    private $typePeriod;

    /**
     * Période de la performance (= dernier jour de la période).
     *
     * @var DateTimeImmutable
     */
    private $period;

    /**
     * Montant cumulé déjà investi.
     *
     * @var float
     */
    private $investCumul;

    /**
     * Valorisation cumulée en cours.
     *
     * @var float
     */
    private $valuation;

    /**
     * Objet PerfItem d'avant pour le calcul.
     *
     * @var PerfItem
     */
    private $previous;

    /**
     * Constructeur.
     *
     * @param int $typePeriod
     */
    public function __construct(int $typePeriod)
    {
        $this->typePeriod = $typePeriod;
    }

    public function setPeriod(DateTime $period): self
    {
        $period = DateTimeImmutable::createFromMutable($period);

        switch ($this->typePeriod) {
            case Performance::MONTH:
                $this->period = $period->modify('last day of this month');
                break;

            case Performance::QUARTER:
                $this->period = DateRange::getLastDayOfQuarter($period);
                break;

            case Performance::YEAR:
                $this->period = $period->modify('last day of december');
                break;
        }

        return $this;
    }

    public function getPeriod(): DateTimeImmutable
    {
        return $this->period;
    }

    public function setInvestCumul(float $investCumul): self
    {
        $this->investCumul = $investCumul;

        return $this;
    }

    public function getInvestCumul(): float
    {
        return $this->investCumul;
    }

    public function setValuation(float $valuation): self
    {
        $this->valuation = $valuation;

        return $this;
    }

    public function getValuation(): float
    {
        return $this->valuation;
    }

    public function setPrevious(?self $perfItem): self
    {
        $this->previous = $perfItem;

        return $this;
    }

    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    /**
     * Ajoute un montant investi durant la période.
     *
     * @param float $amount
     *
     * @return self
     */
    public function addInvest(float $amount): self
    {
        $this->investCumul += $amount;

        return $this;
    }

    /**
     * Ajoute une opération de valorisation durant la période.
     *
     * @param float $amount
     *
     * @return self
     */
    public function addValuation(float $amount): self
    {
        $this->valuation += $amount;

        return $this;
    }

    /**
     * Retourne la variation par rapport à la période précedente.
     *
     * @return float
     */
    public function getVariation(): float
    {
        if (null === $this->previous) {
            return $this->valuation;
        }

        return $this->valuation - $this->previous->getValuation();
    }

    /**
     * Retounre le montant investi durant cette période.
     *
     * @return float
     */
    public function getVersement(): float
    {
        if (null === $this->previous) {
            return $this->investCumul;
        }

        return $this->investCumul - $this->previous->getInvestCumul();
    }

    /**
     * Rettourne la performance cumulée.
     *
     * @return float
     */
    public function getCumulPerf(): float
    {
        return ($this->valuation - $this->investCumul) / $this->investCumul;
    }

    /**
     * Retourne la performance par rapport à la période précédente.
     *
     * @return float
     */
    public function getPerformance(): float
    {
        if (null === $this->previous) {
            return $this->getCumulPerf();
        }

        return ($this->getVariation() - $this->getVersement()) / ($this->previous->getValuation() + $this->getVersement());
    }
}
