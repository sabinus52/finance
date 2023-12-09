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
     *
     * @var float
     */
    private $balance;

    /**
     * Solde rapproché.
     *
     * @var float
     */
    private $reconBalance;

    /**
     * Rapprochement en cours à solder.
     *
     * @var float
     */
    private $reconCurrent;

    /**
     * Montant investi dans les placements.
     *
     * @var float
     */
    private $investment;

    /**
     * Montant investi dans les placements.
     *
     * @var float
     */
    private $repurchase;

    /**
     * Contructeur.
     */
    public function __construct()
    {
        $this->balance = 0.0;
        $this->reconBalance = 0.0;
        $this->reconCurrent = 0.0;
        $this->investment = 0.0;
        $this->repurchase = 0.0;
    }

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
