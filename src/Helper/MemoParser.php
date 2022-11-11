<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Values\AccountType;
use App\Values\Payment;
use App\Values\StockPosition;
use ArrayObject;
use Exception;

/**
 * Classe pour parser le champs "Mémo" du fichier QIF
 * pour la prise en compte des investissements boursiers et de capitalisation.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class MemoParser
{
    public const STOCK_PORTFOLIO = 'CA Compte Titres';
    public const STOCK_PEA = 'CA PEA Titres';

    /**
     * @var ImportHelper
     */
    private $helper;

    /**
     * Liste des inverstissement à créer et à affecter sur une assurance vie.
     *
     * @var ArrayObject
     */
    private $investments;

    /**
     * @var Account
     */
    private $accountPEA;

    /**
     * Constructeur.
     *
     * @param ImportHelper $helper
     * @param array<mixed> $options
     */
    public function __construct(ImportHelper $helper, array $options)
    {
        $this->helper = $helper;
        $this->investments = new ArrayObject();

        // Si on parse le champs mémo, alors on utilise les comptes Titres
        if (false !== $options['parse-memo']) {
            $accountTitres = $this->helper->getAccount(self::STOCK_PORTFOLIO);
            $accountTitres->setType(new AccountType(41));
        }
        if (false !== $options['pea']) {
            $this->accountPEA = $this->helper->getAccount($options['pea']);
            $accountTitres = $this->helper->getAccount(self::STOCK_PEA);
            $accountTitres->setType(new AccountType(41));
        }
    }

    public function parse(string $memo, string $account, string $date, float $amount, string $recipient, string $category, string $state): bool
    {
        // Versement:[Compte] -> Versement
        $operation = strstr($memo, ':', true);

        switch ($operation) {
            case 'Versement':
                $this->parseVersement($memo, $account, $date, $amount, $state);

                return false;

            case 'Stock':
                $this->parseStockPosition($memo, $account, $date, $amount, $recipient, $category, $state);

                return false;

            case 'Dividendes':
                $this->parseStockDividende($memo, $account, $date, $amount, $recipient, $category, $state);

                return false;

            case 'Virement':
                return true;

            default:
                if (false !== strpos($memo, ':')) {
                    throw new Exception('Memo peut être incorrect');
                }

                return true;
        }
    }

    private function parseVersement(string $memo, string $account, string $date, float $amount, string $state): void
    {
        $accountPlacement = $this->getPlacement($memo);
        if (null === $accountPlacement) {
            throw new Exception('Compte de placement introuvable dans le memo');
        }
        $amountPlacement = $this->getAmountVersement($memo, $amount);

        // Toujours compte débiteur = Compte courant où se fait le prélèvement
        $transactionSource = $this->helper->createTransaction(
            $account,
            $date,
            $amount,
            Recipient::VIRT_NAME,
            Category::getBaseCategoryLabel('INVS-'),
            '',
            $state,
            new Payment(Payment::INTERNAL)
        );
        // Toujours versement sur le placement
        $transactionTarget = $this->helper->createTransaction(
            $accountPlacement,
            $date,
            $amountPlacement,
            Recipient::VIRT_NAME,
            Category::getBaseCategoryLabel('VERS+'),
            '',
            0,
            new Payment(Payment::INTERNAL)
        );

        // Sauvegarde du virements pour assoaciations des clés entre les 2 transactions
        $this->investments[] = [
            'source' => $transactionSource,
            'target' => $transactionTarget,
        ];

        // Change le type de placement trouvé
        $this->setAccountType($accountPlacement);
    }

    private function parseStockPosition(string $memo, string $account, string $date, float $amount, string $recipient, string $category, string $state): void
    {
        $stock = $this->getPlacement($memo);
        if (null === $stock) {
            throw new Exception('Compte de placement introuvable dans le memo');
        }
        $volume = $this->getStockVolume($memo);
        if (null === $volume) {
            throw new Exception('Volume de titre (v=*) introuvable dans le memo');
        }
        $price = $this->getStockPrice($memo);
        if (null === $price) {
            throw new Exception('Prix du titre (p=*) introuvable dans le memo');
        }

        $payment = ($amount > 0) ? new Payment(Payment::DEPOT) : new Payment(Payment::PRELEVEMENT);
        $transaction = $this->helper->createTransaction($account, $date, $amount, $recipient, $category, $memo, $state, $payment);

        $operation = $this->helper->createStockPortfolio(
            ($account === $this->accountPEA->getFullName()) ? $this->helper->getAccount(self::STOCK_PEA) : $this->helper->getAccount(self::STOCK_PORTFOLIO),
            $date,
            $amount,
            $stock,
            ($amount < 0) ? new StockPosition(StockPosition::BUYING) : new StockPosition(StockPosition::SELLING),
            $volume,
            $price
        );
        $operation->setTransaction($transaction);
    }

    private function parseStockDividende(string $memo, string $account, string $date, float $amount, string $recipient, string $category, string $state): void
    {
        $stock = $this->getPlacement($memo);
        if (null === $stock) {
            throw new Exception('Compte de placement introuvable dans le memo');
        }

        $payment = ($amount > 0) ? new Payment(Payment::DEPOT) : new Payment(Payment::PRELEVEMENT);
        $transaction = $this->helper->createTransaction($account, $date, $amount, $recipient, $category, $memo, $state, $payment);

        $operation = $this->helper->createStockPortfolio(
            ($account === $this->accountPEA->getFullName()) ? $this->helper->getAccount(self::STOCK_PEA) : $this->helper->getAccount(self::STOCK_PORTFOLIO),
            $date,
            $amount,
            $stock,
            new StockPosition(StockPosition::DIVIDEND),
            null,
            null
        );
        $operation->setTransaction($transaction);
    }

    /**
     * Affecte le type de placement en tant que "Assurance Vie" par défaut.
     *
     * @param string $account
     */
    private function setAccountType(string $account): void
    {
        $account = $this->helper->getAccount($account);
        $account->setType(new AccountType(51));
    }

    /**
     * Affecte les associations des transactions pour les placements.
     */
    public function setAssocInvestment(): void
    {
        foreach ($this->investments as $investment) {
            /** @var Transaction $transactionSource */
            $transactionSource = $investment['source'];
            /** @var Transaction $transactionTarget */
            $transactionTarget = $investment['target'];

            $transactionSource->setTransfer($transactionTarget);
            $transactionTarget->setTransfer($transactionSource);
        }
    }

    /**
     * Retourne le compte de placement ou le nom du titre boursier.
     * Exemple :
     *  Versement:[Mon placement] -> Mon placement
     *  Stock:[Crédit Agricole SA] -> Crédit Agricole SA.
     *
     * @param string $memo
     *
     * @return string|null
     */
    private function getPlacement(string $memo): ?string
    {
        preg_match('/:\[(.*)\]/', $memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Retourne le montant du placement sur les comptes de capitalisation si renseigné
     * ( <> montant débité sur le compte courant)
     * Exemple : Versement:[Mon placement] €1000 -> 1000.
     *
     * @param string $memo
     * @param float  $amount
     *
     * @return float
     */
    private function getAmountVersement(string $memo, float $amount): float
    {
        $amount = $amount * -1;

        preg_match('/€([0-9]*[.]?[0-9]+)/', $memo, $matches);
        if (isset($matches[1])) {
            $amount = $this->helper->getAmount($matches[1]);
        }

        return $amount;
    }

    /**
     * Retourne le volume en achat ou vente de titres
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 10.
     *
     * @param string $memo
     *
     * @return float|null
     */
    private function getStockVolume(string $memo): ?float
    {
        preg_match('/v=([0-9]*[.]?[0-9]+)/', $memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Retourne le prix du titre lors de l'achat ou la vente
     * Exemple : Stock:[Crédit Agricole SA] v=10 p=12.34 -> 12.34.
     *
     * @param string $memo
     *
     * @return float|null
     */
    private function getStockPrice(string $memo): ?float
    {
        preg_match('/p=([0-9]*[.]?[0-9]+)/', $memo, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        return (float) $matches[1];
    }
}
