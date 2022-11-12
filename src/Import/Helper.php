<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Import;

use App\Entity\Account;
use App\Entity\Stock;
use App\Entity\StockPortfolio;
use App\Entity\Transaction;
use App\Values\Payment;
use App\Values\StockPosition;
use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Classe d'aide pour l'omport des transactions venant d'un programme extérieur.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Helper
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Statistiques de l'import.
     *
     * @var Statistic
     */
    public $statistic;

    /**
     * Liste des données associées (Account, Recepient, ...).
     *
     * @var AssocDatas
     */
    public $assocDatas;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        $this->statistic = new Statistic();
        $this->assocDatas = new AssocDatas($manager);
    }

    /**
     * Création d'une transaction.
     *
     * @param QifItem $item
     *
     * @return Transaction
     */
    public function createTransaction(QifItem $item): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAccount($item->getAccount());
        $transaction->setDate($item->getDate());
        $transaction->setRecipient($item->getRecipient());
        $transaction->setMemo($item->getMemo());
        $transaction->setAmount($item->getAmount());
        $transaction->setState($item->getState());
        $transaction->setPayment($item->getPayment());
        $transaction->setCategory($item->getCategory());

        $this->statistic->incTransaction($transaction);
        $this->entityManager->persist($transaction);

        return $transaction;
    }

    /**
     * Création d'une ligne d'opération boursière.
     *
     * @param QifItem        $item
     * @param Account|string $wallet
     * @param Stock|string   $stock
     * @param StockPosition  $operation
     * @param float|null     $volume
     * @param float|null     $price
     *
     * @return StockPortfolio
     */
    public function createStockPortfolio(QifItem $item, $wallet, $stock, StockPosition $operation, ?float $volume, ?float $price): StockPortfolio
    {
        if (!$wallet instanceof Account) {
            $wallet = $this->assocDatas->getAccount($wallet, $item->getDate());
        }
        if (!$stock instanceof Stock) {
            $stock = $this->assocDatas->getStock($stock);
        }
        // Calcul de la commission
        $fee = (null === $volume || null === $price) ? null : abs($item->getAmount()) - ($volume * $price);

        // Création de l'opération boursière
        $portfolio = new StockPortfolio();
        $portfolio->setDate($item->getDate());
        $portfolio->setPosition($operation);
        $portfolio->setVolume($volume);
        $portfolio->setPrice($price);
        $portfolio->setFee($fee);
        $portfolio->setTotal($item->getAmount());
        $portfolio->setStock($stock);
        $portfolio->setAccount($wallet);

        $this->entityManager->persist($portfolio);

        return $portfolio;
    }

    /**
     * Retourne le code du moyen de paiement.
     *
     * @param string $searchPayment
     *
     * @return Payment
     */
    public function getPayment(string $searchPayment): Payment
    {
        $matches = new ArrayObject([
            0 => Payment::INTERNAL,
            1 => Payment::CARTE,
            2 => Payment::CHEQUE,
            3 => Payment::ESPECE,
            4 => Payment::VIREMENT,
            5 => Payment::CARTE,
            6 => Payment::VIREMENT,
            7 => Payment::ELECTRONIC,
            8 => Payment::DEPOT,
            9 => Payment::PRELEVEMENT,
            10 => Payment::PRELEVEMENT,
            11 => Payment::PRELEVEMENT,
        ]);
        $searchPayment = (int) $searchPayment;
        if ($matches->offsetExists($searchPayment)) {
            return new Payment($matches->offsetGet($searchPayment));
        }

        return new Payment(Payment::VIREMENT);
    }
}
