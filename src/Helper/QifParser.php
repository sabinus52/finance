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
use ArrayObject;
use SplFileObject;

/**
 * Classe pour parser les fichiers QIF.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class QifParser
{
    public const DATE_FORMAT = 'm/d/Y';

    public const ACCOUNT = '!Account';
    public const TRANST_BANK = '!Type:Bank';
    public const TRANST_CASH = '!Type:Cash';
    public const TRANST_CCARD = '!Type:CCard';
    public const TRANST_INVST = '!Type:Invst';
    public const TRANST_OTH_A = '!Type:Oth A';
    public const TRANST_OTH_L = '!Type:Oth L';
    public const CATEGORY = '!Type:Cat';
    public const TCLASS = '!Type:Class';
    public const MEMORIZED = '!Type:Memorized';

    /**
     * @var SplFileObject
     */
    private $file;

    /**
     * @var ImportHelper
     */
    private $helper;

    /**
     * Mode du type de section à ajouter (ACCOUNT, TRANST_BANK, ...).
     *
     * @var string
     */
    private $mode;

    /**
     * Liste des virements à créer et à affecter sur le compte créditeur.
     *
     * @var ArrayObject
     */
    private $transfers;

    /**
     * Liste des inverstissement à créer et à affecter sur une assurance vie.
     *
     * @var ArrayObject
     */
    private $investments;

    /**
     * Compte en cours d'importation.
     *
     * @var ArrayObject
     */
    private $account;

    /**
     * Constructeur.
     *
     * @param SplFileObject $file
     * @param ImportHelper  $helper
     */
    public function __construct(SplFileObject $file, ImportHelper $helper)
    {
        $this->file = $file;
        $this->helper = $helper;
        $this->account = new ArrayObject();
        $this->transfers = new ArrayObject();
        $this->investments = new ArrayObject();
    }

    /**
     * Parse le fichier QIF.
     */
    public function parse(): void
    {
        $item = [];
        foreach ($this->file as $line) {
            $line = (string) str_replace(["\n", "\r"], ['', ''], $line);  /** @phpstan-ignore-line */
            switch ($line) {
                case self::ACCOUNT:
                    $this->mode = self::ACCOUNT;
                    break;
                case self::TRANST_BANK:
                    $this->mode = self::TRANST_BANK;
                    break;
                case self::TRANST_CASH:
                    $this->mode = self::TRANST_CASH;
                    break;
                case self::TRANST_CCARD:
                    $this->mode = self::TRANST_CCARD;
                    break;
                case self::TRANST_INVST:
                    $this->mode = self::TRANST_INVST;
                    break;
                case self::TRANST_OTH_A:
                    $this->mode = self::TRANST_OTH_A;
                    break;
                case self::TRANST_OTH_L:
                    $this->mode = self::TRANST_OTH_L;
                    break;
                case self::CATEGORY:
                    $this->mode = self::CATEGORY;
                    break;
                case self::TCLASS:
                    $this->mode = self::TCLASS;
                    break;
                case self::MEMORIZED:
                    $this->mode = self::MEMORIZED;
                    break;
                case '^':
                    // Fin de la liste d'un élément alors on créé l'élément
                    $this->createItem($item);
                    // Nouvel élément à importer
                    $item = [];
                    break;
                default:
                    $item[] = $line;
                    break;
            }
        }
    }

    /**
     * Création du compte ou de la transaction en fonction du curseur du fichier.
     *
     * @param array<string> $item
     */
    private function createItem(array $item): void
    {
        switch ($this->mode) {
            case self::ACCOUNT:
                $this->parseAccount($item);
                break;
            case self::TRANST_BANK:
            case self::TRANST_CASH:
            case self::TRANST_CCARD:
            case self::TRANST_OTH_A:
            case self::TRANST_OTH_L:
                $this->parseTransaction($item);
                break;
            case self::TRANST_INVST:
                break;

            default:
                break;
        }
    }

    /**
     * Parse la section des infos du compte.
     *
     * - N	Name
     * - T	Type of account
     * - D	Description
     * - L	Credit limit (only for credit card accounts)
     * - /	Statement balance date
     * - $	Statement balance amount
     *
     * @param array<string> $item
     */
    public function parseAccount(array $item): void
    {
        $this->account = new ArrayObject();
        foreach ($item as $line) {
            switch ($line[0]) {
                case 'N':
                    $this->account['name'] = substr($line, 1);
                    break;
                case 'T':
                    $this->account['type'] = substr($line, 1);
                    break;
                default:
                    echo '';
                    break;
            }
        }
        $account = $this->helper->getAccount($this->account['name']);
        if ('Oth A' === $this->account['type'] || 'Oth L' === $this->account['type']) {
            $account->setType(new AccountType(26));
        }
    }

    /**
     * Parse la section des infos de la transaction.
     *
     * - D	Date
     * - T	Amount
     * - C	Cleared status
     * - N	Num (check or reference number)
     * - P	Payee
     * - M	Memo
     * - A	Address (up to five lines; the sixth line is an optional message)
     * - L	Category (Category/Subcategory/Transfer/Class)
     * - S	Category in split (Category/Transfer/Class)
     * - E	Memo in split
     * - $	Dollar amount of split
     *
     * @param array<string> $item
     */
    public function parseTransaction(array $item): void
    {
        $date = $strAmount = $state = $recipient = $category = $memo = '';
        $amount = 0;

        foreach ($item as $line) {
            if ('D' === $line[0]) {
                $date = substr($line, 1);
            } elseif ('T' === $line[0]) {
                $strAmount = substr($line, 1);
                $amount = $this->helper->getAmount($strAmount);
            } elseif ('C' === $line[0]) {
                $state = substr($line, 1);
            } elseif ('P' === $line[0]) {
                $recipient = substr($line, 1);
            } elseif ('M' === $line[0]) {
                $memo = substr($line, 1);
            } elseif ('L' === $line[0]) {
                $category = substr($line, 1);
            }
        }

        if ('' !== $category && '[' === $category[0] && ']' === $category[strlen($category) - 1]) {
            /**
             * Détection d'un virement.
             */
            $transactionSource = $this->helper->createTransaction(
                $this->account['name'],
                $date,
                $amount,
                Recipient::VIRT_NAME,
                ($amount > 0) ? Category::getBaseCategoryLabel('VIRT+') : Category::getBaseCategoryLabel('VIRT-'),
                $memo,
                $state,
                new Payment(Payment::INTERNAL)
            );
            $transactionTarget = $this->helper->createTransaction(
                substr($category, 1, -1),
                $date,
                $amount * -1,
                Recipient::VIRT_NAME,
                ($amount * -1 > 0) ? Category::getBaseCategoryLabel('VIRT+') : Category::getBaseCategoryLabel('VIRT-'),
                $memo,
                $state,
                new Payment(Payment::INTERNAL)
            );
            // Sauvegarde du virements pour assoaciations des clés entre les 2 transactions
            $this->transfers[] = [
                'source' => $transactionSource,
                'target' => $transactionTarget,
            ];
        } elseif ('Versement:' === substr($memo, 0, 10)) {
            /**
             * Détection d'un investissement.
             */
            $placement = substr($memo, 11, -1);
            // Toujours compte débiteur
            $transactionSource = $this->helper->createTransaction(
                $this->account['name'],
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
                $placement,
                $date,
                $amount * -1,
                Recipient::VIRT_NAME,
                Category::getBaseCategoryLabel('VERS+'),
                '',
                0,
                new Payment(Payment::INTERNAL)
            );
            $accPlacement = $this->helper->getAccount($placement);
            $accPlacement->setType(new AccountType(51));
            // Sauvegarde du virements pour assoaciations des clés entre les 2 transactions
            $this->investments[] = [
                'source' => $transactionSource,
                'target' => $transactionTarget,
            ];
        } else {
            /**
             * Transaction standard.
             */
            $payment = ($amount > 0) ? new Payment(Payment::DEPOT) : new Payment(Payment::PRELEVEMENT);
            $this->helper->createTransaction($this->account['name'], $date, $strAmount, $recipient, $category, $memo, $state, $payment);
        }
    }

    /**
     * Affecte les associations des transactions pour les virements internes.
     */
    public function setAssocTransfer(): void
    {
        foreach ($this->transfers as $transfer) {
            /** @var Transaction $transactionSource */
            $transactionSource = $transfer['source'];
            /** @var Transaction $transactionTarget */
            $transactionTarget = $transfer['target'];

            $transactionSource->setTransfer($transactionTarget);
            $transactionTarget->setTransfer($transactionSource);
        }
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
}
