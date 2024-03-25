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
use App\Values\AccountType;
use App\Values\StockPosition;

/**
 * Classe pour parser le champs "Mémo" du fichier QIF
 * pour la prise en compte des investissements boursiers et de capitalisation.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class MemoParser
{
    final public const ACC_WALLET_DEFAULT = 'MyBank Compte Titres';

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
     */
    public function __construct(private readonly Helper $helper)
    {
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
     */
    public function parse(QifItem $item): bool
    {
        // Versement:[Compte] -> Versement
        preg_match('/([A-za-z]+):\[(.*)\]/', $item->getMemo(), $matches);
        if (!isset($matches[1])) {
            $operation = strstr($item->getMemo(), ':', true);
            if ($operation) {
                throw new \Exception('Memo est peut être incorrect');
            }

            return true;
        }

        switch ($matches[1]) {
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

            case 'Projet':
                $this->parseProject($item);

                return false;

            default:
                if (str_contains($item->getMemo(), ':')) {
                    throw new \Exception('Memo est peut être incorrect');
                }

                return true;
        }
    }

    /**
     * Parse dans le cas d'un versement sur un compte de capitalisation.
     */
    private function parseVersement(QifItem $item): void
    {
        $memo = new MemoHelper($item->getMemo());
        $accountPlacement = $memo->getLabelMemo();
        if (null === $accountPlacement) {
            throw new \Exception('Compte de placement introuvable dans le memo');
        }
        $amountPlacement = $memo->getAmountVersement($item->getAmount());

        $this->helper->createTransactionTransfer($item, Category::INVESTMENT, $accountPlacement, $amountPlacement);

        // Change le type de placement trouvé
        $this->setAccountType($accountPlacement);
    }

    /**
     * Parse dans le cas d'un achat ou d'une vente d'un titre boursier.
     */
    private function parseStockPosition(QifItem $item): void
    {
        $memo = new MemoHelper($item->getMemo());
        $stock = $memo->getLabelMemo();
        if (null === $stock) {
            throw new \Exception('Compte de placement introuvable dans le memo');
        }
        $volume = $memo->getStockVolume();
        if (null === $volume) {
            throw new \Exception('Volume de titre (v=*) introuvable dans le memo');
        }
        $price = $memo->getStockPrice();
        if (null === $price) {
            throw new \Exception('Prix du titre (p=*) introuvable dans le memo');
        }

        $position = ($item->getAmount() < 0) ? StockPosition::BUYING : StockPosition::SELLING;
        $this->helper->createTransationStockPosition($item, $this->getWallet($item), $position, $stock, $volume, $price);
    }

    /**
     * Parse dans le cas d'un versement de dividende.
     */
    private function parseStockDividende(QifItem $item): void
    {
        $memo = new MemoHelper($item->getMemo());
        $stock = $memo->getLabelMemo();
        if (null === $stock) {
            throw new \Exception('Compte de placement introuvable dans le memo');
        }

        $this->helper->createTransationStockPosition($item, $this->getWallet($item), StockPosition::DIVIDEND, $stock, null, null);
    }

    /**
     * Parse dans le cas d'un projet.
     */
    private function parseProject(QifItem $item): void
    {
        $memo = new MemoHelper($item->getMemo());
        $project = $memo->getLabelMemo();
        if (null === $project) {
            throw new \Exception('Nom du projet introuvable dans le memo');
        }
        $this->helper->createTransationStandard($item, $project);
    }

    /**
     * Retourne le compte de portefeuille à utiliser.
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
     */
    private function setAccountType(string $account): void
    {
        $account = $this->helper->assocDatas->getAccount($account);
        $account->setType(new AccountType(51));
    }
}
