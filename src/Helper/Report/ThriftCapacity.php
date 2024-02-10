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
use App\Entity\Transaction;
use App\Values\AccountType;
use App\Values\TransactionType;

/**
 * Gestion du calcul du rapport de la capacité d'épargne.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ThriftCapacity
{
    public const BY_YEAR = 'year';
    public const BY_MONTH = 'month';

    /**
     * Type de la période (mois ou année).
     *
     * @var string
     */
    private $periodType;

    /**
     * Tableau du résultat.
     *
     * @var ThriftItem[]
     */
    private $results;

    /**
     * Constructeur.
     *
     * @param string $periodType
     */
    public function __construct(string $periodType)
    {
        $this->periodType = $periodType;
        $this->results = [];
    }

    /**
     * Retourne le résultat.
     *
     * @param int $lastCount
     *
     * @return ThriftItem[]
     */
    public function getResults(int $lastCount = null): array
    {
        if (null !== $lastCount) {
            return array_slice($this->results, -$lastCount);
        }

        return $this->results;
    }

    /**
     * Ajoute une transaction pour le calcul.
     *
     * @param Transaction $transaction
     */
    public function addTransaction(Transaction $transaction): void
    {
        $unit = $transaction->getAccount()->getUnit();

        $period = $this->getPeriod($transaction);
        if (!array_key_exists($period, $this->results)) {
            $this->results[$period] = new ThriftItem($transaction->getDate());
        }

        if (TransactionType::TRANSFER === $transaction->getType()->getValue()) {
            $virement = $transaction->getTransfer();
            if ($virement->getAccount()->getUnit() !== $unit) {
                $this->results[$period]->addAmount($transaction->getAmount());
            }
            // Transaction d'épargne pour la colonne de ce qui a été épargné
            if (AccountType::EPARGNE_LIQUIDE === $transaction->getAccount()->getType()->getTypeCode()) {
                if (0 === $transaction->getAmount() % 10) {
                    $this->results[$period]->addThrift($transaction->getAmount());
                }
            }
        } elseif (Category::INVESTMENT === $transaction->getCategory()->getCode()) {
            // Si l'investissement est différent de la sommme réellement versé ( frais par exemple )
            $versement = $transaction->getTransfer();
            $this->results[$period]->addInvest($versement->getAmount());
            $this->results[$period]->addExpense($transaction->getAmount() + $versement->getAmount());
        } else {
            $this->results[$period]->addAmount($transaction->getAmount());
        }
    }

    /**
     * Retourne la période.
     *
     * @param Transaction $transaction
     *
     * @return string
     */
    private function getPeriod(Transaction $transaction): string
    {
        switch ($this->periodType) {
            case self::BY_MONTH:
                return $transaction->getDate()->format('Y-m');
            case self::BY_YEAR:
                return $transaction->getDate()->format('Y');
        }

        return '';
    }
}
