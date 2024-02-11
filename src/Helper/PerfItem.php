<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

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
     * Période de la performance (= dernier jour de la période).
     *
     * @var \DateTimeImmutable
     */
    private $period;

    /**
     * Montant investi durant la période.
     */
    private float $investment = 0.0;

    /**
     * Montant cumulé déjà investi.
     */
    private ?float $investmentCumul = null;

    /**
     * Montant du rachat partiel durant la période.
     */
    private float $repurchase = 0.0;

    /**
     * Montant cumulé des rachats partiels.
     */
    private ?float $repurchaseCumul = null;

    /**
     * Valorisation cumulée en cours.
     */
    private ?float $valuation = null;

    /**
     * Objet PerfItem d'avant pour le calcul.
     */
    private ?PerfItem $previous = null;

    /**
     * Constructeur.
     */
    public function __construct(private readonly int $typePeriod)
    {
    }

    public function setPeriod(\DateTimeImmutable $period): self
    {
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

    public function getPeriod(): \DateTimeImmutable
    {
        return $this->period;
    }

    public function setInvestment(float $invest): self
    {
        $this->investment = abs($invest);

        return $this;
    }

    public function getInvestment(): float
    {
        return $this->investment;
    }

    public function getInvestmentCumul(): float
    {
        if (null === $this->investmentCumul) {
            $this->investmentCumul = $this->accumlateInvestment();
        }

        return $this->investmentCumul;
    }

    public function setRepurchase(float $repurchase): self
    {
        $this->repurchase = abs($repurchase);

        return $this;
    }

    public function getRepurchase(): float
    {
        return $this->repurchase;
    }

    public function getRepurchaseCumul(): float
    {
        if (null === $this->repurchaseCumul) {
            $this->repurchaseCumul = $this->accumlateRepurchase();
        }

        return $this->repurchaseCumul;
    }

    public function setValuation(float $valuation): self
    {
        $this->valuation = $valuation;

        return $this;
    }

    public function getValuation(): ?float
    {
        // Prend celui d'avant s'il est vide
        if (null === $this->valuation && $this->previous instanceof self) {
            return $this->previous->getValuation();
        }

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
     * Calcule le cumul du montant investi et la valorisation.
     */
    public function calculate(): void
    {
        $this->valuation = $this->getValuation();
        $this->investmentCumul = $this->accumlateInvestment();
        $this->repurchaseCumul = $this->accumlateRepurchase();
    }

    /**
     * Ajoute un montant investi durant la période.
     */
    public function addInvestment(float $amount): self
    {
        $this->investment += abs($amount);

        return $this;
    }

    /**
     * Ajoute un montant de rachat durant la période.
     */
    public function addRepurchase(float $amount): self
    {
        $this->repurchase += abs($amount);

        return $this;
    }

    /**
     * Ajoute un montant de valorisation durant la période.
     */
    public function addValuation(float $amount): self
    {
        $this->valuation += abs($amount);

        return $this;
    }

    /**
     * Retourne le cumul du montant investi depuis le début.
     */
    public function accumlateInvestment(): float
    {
        $investmentCumul = $this->investment;

        // Appeler récursivement le parent pour accumuler le montant investi
        if ($this->previous instanceof self) {
            $investmentCumul += $this->previous->accumlateInvestment();
        }

        return $investmentCumul;
    }

    /**
     * Retourne le cumul du montant des rachats depuis le début.
     */
    public function accumlateRepurchase(): float
    {
        $repurchaseCumul = $this->repurchase;

        // Appeler récursivement le parent pour accumuler le montant des rachats
        if ($this->previous instanceof self) {
            $repurchaseCumul += $this->previous->accumlateRepurchase();
        }

        return $repurchaseCumul;
    }

    /**
     * Retourne la variation par rapport à la période précedente.
     * Nécesssaire pour les périodes "glissantes".
     */
    public function getVariation(): float
    {
        if (!$this->previous instanceof self) {
            return (float) $this->getValuation();
        }

        return $this->getValuation() - $this->previous->getValuation() + $this->getRepurchaseCumul() - $this->previous->getRepurchaseCumul();
    }

    /**
     * Retourne le versement investi par rapport à la période précedente.
     * Nécesssaire pour les périodes "glissantes".
     */
    public function getVersement(): float
    {
        if (!$this->previous instanceof self) {
            return $this->getInvestment();
        }

        return $this->getInvestmentCumul() - $this->previous->getInvestmentCumul();
    }

    /**
     * Retourne la performance cumulée.
     */
    public function getCumulPerf(): ?float
    {
        // Test si DIV 0
        $investCumul = $this->getInvestmentCumul();
        if (0.0 === $investCumul) {
            return null;
        }

        return ($this->getValuation() + $this->getRepurchaseCumul() - $this->getInvestmentCumul()) / $investCumul;
    }

    /**
     * Retourne la performance par rapport à la période précédente.
     */
    public function getPerformance(): ?float
    {
        if (!$this->previous instanceof self) {
            return null;
        }

        // Test si DIV 0
        $quotient = $this->previous->getValuation() + $this->getVersement();
        if (0.0 === $quotient) {
            return null;
        }

        return ($this->getVariation() - $this->getVersement()) / ($this->previous->getValuation() + $this->getVersement());
    }
}
