<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper\Report;

/**
 * Element de la capacité d'épargne sur les dépenses et revenues durant le mois.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ThriftItem
{
    /**
     * Total des dépenses durant la période.
     */
    private float $expense = 0.0;

    /**
     * Total des revenues durant la période.
     */
    private float $income = 0.0;

    /**
     * Total des montants investis durant la période.
     */
    private float $invest = 0.0;

    /**
     * Montant épargné durant la période.
     */
    private float $thrift = 0.0;

    public function __construct(private readonly \DateTimeImmutable $period)
    {
    }

    public function getPeriod(): \DateTimeImmutable
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
