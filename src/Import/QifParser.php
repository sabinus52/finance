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
use App\Values\Payment;
use App\Values\TransactionType;
use ArrayObject;
use SplFileObject;
use Throwable;

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
     * @var Helper
     */
    private $helper;

    /**
     * @var MemoParser
     */
    private $memoParser;

    /**
     * @var bool
     */
    private $isParseMemo;

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
     * Compte en cours d'importation.
     *
     * @var ArrayObject
     */
    private $account;

    /**
     * Constructeur.
     *
     * @param SplFileObject $file
     * @param Helper        $helper
     * @param bool          $isParseMemo
     */
    public function __construct(SplFileObject $file, Helper $helper, bool $isParseMemo)
    {
        $this->file = $file;
        $this->helper = $helper;
        $this->isParseMemo = $isParseMemo;
        $this->memoParser = new MemoParser($helper);
        $this->account = new ArrayObject();
        $this->transfers = new ArrayObject();
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
        $this->helper->assocDatas->getAccount($this->account['name']);
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
        $date = $amount = $state = $recipient = $category = $memo = '';
        $isTransactStandard = true;

        foreach ($item as $line) {
            if ('D' === $line[0]) {
                $date = substr($line, 1);
            } elseif ('T' === $line[0]) {
                $amount = substr($line, 1);
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

        $itemFound = new QifItem($this->helper->assocDatas);
        $itemFound->setDate($date)
            ->setAmount($amount)
            ->setState($state)
            ->setRecipient($recipient)
            ->setMemo($memo)
            ->setCategory($category)
            ->setAccount($this->account['name'])
        ;

        if ('' !== $category && '[' === $category[0] && ']' === $category[strlen($category) - 1]) {
            /**
             * Détection d'un virement.
             */
            $this->createTransfer($itemFound, substr($category, 1, -1));
            $isTransactStandard = false;
        } elseif (true === $this->isParseMemo && null !== $itemFound->getMemo()) {
            /**
             * Parse le champs mémp pour les comptes de capitalisation et boursier.
             */
            try {
                $isTransactStandard = $this->memoParser->parse($itemFound);
            } catch (Throwable $th) {
                $this->helper->statistic->addMemoAlert($itemFound->getDate(), $itemFound->getAccount(), $itemFound->getAmount(), $itemFound->getMemo(), $th->getMessage());
            }
        }
        if ($isTransactStandard) {
            /**
             * Transaction standard.
             */
            $this->helper->createTransaction($itemFound);
        }
    }

    /**
     * Créer les 2 transactions lors d'un virement.
     *
     * @param QifItem $item          Transaction trouvée
     * @param string  $accountTarget Compte cible du virement
     */
    private function createTransfer(QifItem $item, string $accountTarget): void
    {
        $item->setRecipient(Recipient::VIRT_NAME);
        $item->setPayment(Payment::INTERNAL);
        $item->setType(TransactionType::VIREMENT);

        // Virement source
        $item->setCategoryWithCode(Category::VIREMENT);
        $transactionSource = $this->helper->createTransaction($item);

        // Virement cible
        $item->setAccount($accountTarget);
        $item->setAmount($item->getAmount() * -1);
        $item->setCategoryWithCode(Category::VIREMENT);
        $transactionTarget = $this->helper->createTransaction($item);

        // Sauvegarde du virements pour assoaciations des clés entre les 2 transactions
        $this->transfers[] = [
            'source' => $transactionSource,
            'target' => $transactionTarget,
        ];
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

        if (true === $this->isParseMemo) {
            $this->memoParser->setAssocInvestment();
        }
    }
}
