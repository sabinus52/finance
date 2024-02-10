<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Report;

use DateTime;

/**
 * Element de la capacité d'épargne sur les dépenses et revenues durant le mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ThriftItem
{
    /**
     * Date de la période.
     *
     * @var DateTime
     */
    private $period;

    /**
     * Total des dépenses durant la période.
     *
     * @var float
     */
    private $expense;

    /**
     * Total des revenues durant la période.
     *
     * @var float
     */
    private $income;

    /**
     * Total des montants investis durant la période.
     *
     * @var float
     */
    private $invest;

    /**
     * Montant épargné durant la période.
     *
     * @var float
     */
    private $thrift;

    public function __construct(DateTime $period)
    {
        $this->income = 0.0;
        $this->expense = 0.0;
        $this->invest = 0.0;
        $this->thrift = 0.0;
        $this->period = $period;
    }

    public function getPeriod(): DateTime
    {
        return $this->period;
    }

    public function addAmount(float $amount): self
    {
        if ($amount < 0.0) {
            $this->addExpense($amount);
        } else {
            $this->addIncome($amount);
        }

        return $this;
    }

    public function getExpense(): float
    {
        return $this->expense;
    }

    public function setExpense(float $expense): self
    {
        $this->expense = abs($expense);

        return $this;
    }

    public function addExpense(float $amount): self
    {
        $this->expense += abs($amount);

        return $this;
    }

    public function getIncome(): float
    {
        return $this->income;
    }

    public function setIncome(float $income): self
    {
        $this->income = abs($income);

        return $this;
    }

    public function addIncome(float $amount): self
    {
        $this->income += abs($amount);

        return $this;
    }

    public function getInvest(): float
    {
        return $this->invest;
    }

    public function setInvest(float $invest): self
    {
        $this->invest = abs($invest);

        return $this;
    }

    public function addInvest(float $amount): self
    {
        $this->invest += abs($amount);

        return $this;
    }

    public function getThrift(): float
    {
        return $this->thrift;
    }

    public function setThrift(float $thrift): self
    {
        $this->thrift = $thrift;

        return $this;
    }

    public function addThrift(float $amount): self
    {
        $this->thrift += $amount;

        return $this;
    }

    public function getDiff(): float
    {
        return $this->income - $this->expense;
    }

    public function getCapacity(): float
    {
        return $this->income - $this->expense - $this->invest - $this->thrift;
    }
}
