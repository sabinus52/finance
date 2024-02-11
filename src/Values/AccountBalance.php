<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Values;

/**
 * Metadonnées des différents soldes d'un compte.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class AccountBalance
{
    /**
     * Solde courant du compte.
     */
    private ?float $balance = 0.0;

    /**
     * Solde rapproché.
     */
    private float $reconBalance = 0.0;

    /**
     * Rapprochement en cours à solder.
     */
    private float $reconCurrent = 0.0;

    /**
     * Montant investi dans les placements.
     */
    private ?float $investment = 0.0;

    /**
     * Montant investi dans les placements.
     */
    private ?float $repurchase = 0.0;

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getReconBalance(): ?float
    {
        return $this->reconBalance;
    }

    public function setReconBalance(float $reconBalance): self
    {
        $this->reconBalance = $reconBalance;

        return $this;
    }

    public function getReconCurrent(): ?float
    {
        return $this->reconCurrent;
    }

    public function setReconCurrent(float $reconCurrent): self
    {
        $this->reconCurrent = $reconCurrent;

        return $this;
    }

    public function getInvestment(): ?float
    {
        return $this->investment;
    }

    public function setInvestment(?float $investment): self
    {
        $this->investment = $investment;

        return $this;
    }

    public function getRepurchase(): ?float
    {
        return $this->repurchase;
    }

    public function setRepurchase(?float $repurchase): self
    {
        $this->repurchase = $repurchase;

        return $this;
    }
}
