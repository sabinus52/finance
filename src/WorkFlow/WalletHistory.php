<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\StockWallet;
use App\Entity\Transaction;
use App\Values\StockPosition;
use DateTime;
use Iterator;

/**
 * Historique du portefeuille d'un mois donné.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class WalletHistory implements Iterator
{
    /**
     * Date de fin de mois de l'historique du portefeuille.
     *
     * @var DateTime
     */
    private $date;

    /**
     * Portefeuille.
     *
     * @var StockWallet[]
     */
    private $wallet;

    /**
     * Clonage du portefeuille pour le mois suivant.
     */
    public function __clone()
    {
        foreach ($this->wallet as &$item) {
            $item = clone $item;
        }
    }

    /**
     * Affecte la date du portefeuille.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date->modify('last day of this month');
    }

    /**
     * Retourne la date du portefeuille.
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Ajoute une nouvelle opération boursière.
     *
     * @param Transaction $transaction
     */
    public function addPosition(Transaction $transaction): void
    {
        $transacStock = $transaction->getTransactionStock();
        $id = $transacStock->getStock()->getId();
        if (!isset($this->wallet[$id])) {
            $this->wallet[$id] = new StockWallet();
            $this->wallet[$id]->setStock($transacStock->getStock());
            $this->wallet[$id]->setAccount($transacStock->getAccount());
        }
        if (StockPosition::BUYING === $transacStock->getPositionValue()) {
            $this->wallet[$id]->doBuying($transacStock->getVolume(), $transacStock->getPrice(), $transacStock->getFee());
        } elseif (StockPosition::SELLING === $transacStock->getPositionValue()) {
            $this->wallet[$id]->doSelling($transacStock->getVolume(), $transacStock->getPrice(), $transacStock->getFee());
        } elseif (StockPosition::FUSION_SALE === $transacStock->getPositionValue()) {
            $this->wallet[$id]->doFusionSelling($transacStock->getVolume(), $transacStock->getPrice(), $transacStock->getFee());
        } elseif (StockPosition::FUSION_BUY === $transacStock->getPositionValue()) {
            $beforeStock = $this->wallet[$transacStock->getStock()->getFusionFrom()->getId()];
            $this->wallet[$id]->doFusionBuying($transacStock->getVolume(), $transacStock->getPrice(), $transacStock->getFee(), $beforeStock);
        } elseif (StockPosition::DIVIDEND === $transacStock->getPositionValue()) {
            $this->wallet[$id]->doDividend($transaction->getAmount());
        }
    }

    /**
     * Retourne la valorisation du portefeuille.
     *
     * @return float
     */
    public function getValorisation(): float
    {
        $total = 0.0;
        foreach ($this->wallet as $item) {
            $total += $item->getVolume() * $item->getPrice();
        }

        return round($total, 2);
    }

    /**
     * Retourne le montant investi.
     *
     * @return float
     */
    public function getAmountInvest(): float
    {
        $total = 0.0;
        foreach ($this->wallet as $item) {
            $total += $item->getInvest();
        }

        return round($total, 2);
    }

    public function current()
    {
        return current($this->wallet);
    }

    public function key(): mixed
    {
        return key($this->wallet);
    }

    public function next(): void
    {
        next($this->wallet);
    }

    public function rewind(): void
    {
        reset($this->wallet);
    }

    public function valid(): bool
    {
        $key = key($this->wallet);

        return null !== $key && false !== $key;
    }
}
