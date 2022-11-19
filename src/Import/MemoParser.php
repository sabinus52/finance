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
    public const ACC_WALLET_DEFAULT = 'MyBank Compte Titres';

    /**
     * @var Helper
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
    private $accountPEACaisse;

    /**
     * @var Account
     */
    private $accountPEATitres;

    /**
     * @var Account
     */
    private $accountTitres;

    /**
     * Constructeur.
     *
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
        $this->investments = new ArrayObject();

        // Si on parse le champs mémo, alors on utilise les comptes Titres
        $this->accountTitres = $this->helper->assocDatas->getAccountSpecial(AccountType::ACC_TITRES);
        if (null === $this->accountTitres) {
            $this->accountTitres = $this->helper->assocDatas->getAccount(self::ACC_WALLET_DEFAULT);
            $this->accountTitres->setType(new AccountType(AccountType::ACC_TITRES));
        }
        $this->accountPEACaisse = $this->helper->assocDatas->getAccountSpecial(AccountType::PEA_CAISSE);
        $this->accountPEATitres = $this->helper->assocDatas->getAccountSpecial(AccountType::PEA_TITRES);
    }

    /**
     * Parse le Mémo et retourne l'indiquation s'il faut créer à la suite une transaction standard.
     *
     * @param QifItem $item
     *
     * @return bool
     */
    public function parse(QifItem $item): bool
    {
        // Versement:[Compte] -> Versement
        $operation = strstr($item->getMemo(), ':', true);

        switch ($operation) {
            case 'Versement':
                $this->parseVersement($item);

                return false;

            case 'Stock':
                $this->parseStockPosition($item);

                return false;

            case 'Dividendes':
                $this->parseStockDividende($item);

                return false;

            case 'Virement':
                return true;

            default:
                if (false !== strpos($item->getMemo(), ':')) {
                    throw new Exception('Memo est peut être incorrect');
                }

                return true;
        }
    }

    /**
     * Parse dans le cas d'un versement sur un compte de capitalisation.
     *
     * @param QifItem $item
     */
    private function parseVersement(QifItem $item): void
    {
        $memo = $item->getMemo();
        $accountPlacement = $this->getPlacement($memo);
        if (null === $accountPlacement) {
            throw new Exception('Compte de placement introuvable dans le memo');
        }
        $amountPlacement = $this->getAmountVersement($memo, $item->getAmount());

        // Valeurs communes
        $item->setRecipient(Recipient::VIRT_NAME);
        $item->setPayment(Payment::INTERNAL);

        // Toujours compte débiteur = Compte courant où se fait le prélèvement
        $item->setCategoryWithCode(Category::INVESTMENT);
        $transactionSource = $this->helper->createTransaction($item);

        // Toujours versement sur le placement
        $item->setAccount($accountPlacement);
        $item->setAmount($amountPlacement);
        $item->setState('');
        $item->setCategoryWithCode(Category::INVESTMENT);
        $transactionTarget = $this->helper->createTransaction($item);

        // Sauvegarde du virements pour assoaciations des clés entre les 2 transactions
        $this->investments[] = [
            'source' => $transactionSource,
            'target' => $transactionTarget,
        ];

        // Change le type de placement trouvé
        $this->setAccountType($accountPlacement);
    }

    /**
     * Parse dans le cas d'un achat ou d'une vente d'un titre boursier.
     *
     * @param QifItem $item
     */
    private function parseStockPosition(QifItem $item): void
    {
        $memo = $item->getMemo();
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

        // Transaction standard
        $transaction = $this->helper->createTransaction($item);

        // Opération boursière
        $operation = $this->helper->createStockPortfolio(
            $item,
            $this->getWallet($item),
            $stock,
            ($item->getAmount() < 0) ? new StockPosition(StockPosition::BUYING) : new StockPosition(StockPosition::SELLING),
            $volume,
            $price
        );
        $operation->setTransaction($transaction);
    }

    /**
     * Parse dans le cas d'un versement de dividende.
     *
     * @param QifItem $item
     */
    private function parseStockDividende(QifItem $item): void
    {
        $memo = $item->getMemo();
        $stock = $this->getPlacement($memo);
        if (null === $stock) {
            throw new Exception('Compte de placement introuvable dans le memo');
        }

        // Transaction standard
        $transaction = $this->helper->createTransaction($item);

        // Opération boursière
        $operation = $this->helper->createStockPortfolio(
            $item,
            $this->getWallet($item),
            $stock,
            new StockPosition(StockPosition::DIVIDEND),
            null,
            null
        );
        $operation->setTransaction($transaction);
    }

    /**
     * Retourne le compte de portefeuille à utiliser.
     *
     * @param QifItem $item
     *
     * @return Account
     */
    private function getWallet(QifItem $item): Account
    {
        if (null === $this->accountPEACaisse || null === $this->accountPEATitres) {
            return $this->accountTitres;
        }
        if ($item->getAccount()->getFullName() !== $this->accountPEACaisse->getFullName()) {
            return $this->accountTitres;
        }

        return $this->accountPEATitres;
    }

    /**
     * Affecte le type de placement en tant que "Assurance Vie" par défaut.
     *
     * @param string $account
     */
    private function setAccountType(string $account): void
    {
        $account = $this->helper->assocDatas->getAccount($account);
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
            $amount = (float) $matches[1];
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
