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
use App\Entity\Institution;
use App\Entity\Recipient;
use App\Entity\Stock;
use App\Entity\StockPortfolio;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\InstitutionRepository;
use App\Repository\RecipientRepository;
use App\Repository\StockRepository;
use App\Values\AccountType;
use App\Values\Payment;
use App\Values\StockPosition;
use ArrayObject;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Classe d'aide pour l'omport des transactions venant d'un programme extérieur.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportHelper
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ImportStatistic
     */
    public $statistic;

    /**
     * Liste des organismes.
     *
     * @var ArrayObject
     */
    protected $institutions;

    /**
     * Liste des comptes.
     *
     * @var ArrayObject
     */
    protected $accounts;

    /**
     * Liste des bénéficiaires.
     *
     * @var ArrayObject
     */
    protected $recipients;

    /**
     * Liste des catégories de niveau 1.
     *
     * @var ArrayObject
     */
    protected $catLevel1;

    /**
     * Liste des catégories de niveau 2.
     *
     * @var ArrayObject
     */
    protected $catLevel2;

    /**
     * Liste des titres (actions).
     *
     * @var ArrayObject
     */
    private $stocks;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        $this->statistic = new ImportStatistic();
        $this->institutions = new ArrayObject();
        $this->accounts = new ArrayObject();
        $this->recipients = new ArrayObject();
        $this->catLevel1 = new ArrayObject();
        $this->catLevel2 = new ArrayObject();
        $this->stocks = new ArrayObject();
    }

    /**
     * Chargement des tableaux associatifs pour les liens avec les transactions.
     */
    public function loadAssociations(): void
    {
        /** @var InstitutionRepository $repositoryInstitut */
        $repositoryInstitut = $this->entityManager->getRepository(Institution::class);
        $this->institutions = new ArrayObject($repositoryInstitut->get4Import());

        /** @var AccountRepository $repositoryAccount */
        $repositoryAccount = $this->entityManager->getRepository(Account::class);
        $this->accounts = new ArrayObject($repositoryAccount->get4Import());

        /** @var RecipientRepository $repositoryRecipient */
        $repositoryRecipient = $this->entityManager->getRepository(Recipient::class);
        $this->recipients = new ArrayObject($repositoryRecipient->get4Import());

        /** @var CategoryRepository $repositoryCategory */
        $repositoryCategory = $this->entityManager->getRepository(Category::class);
        $this->catLevel1 = new ArrayObject($repositoryCategory->get4ImportLevel1());
        $this->catLevel2 = new ArrayObject($repositoryCategory->get4ImportLevel2());

        /** @var StockRepository $repositoryStock */
        $repositoryStock = $this->entityManager->getRepository(Stock::class);
        $this->stocks = new ArrayObject($repositoryStock->get4Import());
    }

    /**
     * Création d'une transaction.
     *
     * @param Account|string   $account
     * @param DateTime|string  $date
     * @param float|string     $amount
     * @param Recipient|string $recipient
     * @param Category|string  $category
     * @param string           $memo
     * @param int|string       $state
     * @param Payment|string   $payment
     *
     * @return Transaction
     */
    public function createTransaction($account, $date, $amount, $recipient, $category, ?string $memo, $state, $payment): Transaction
    {
        if (!$date instanceof DateTime) {
            $date = $this->getDateTime($date, QifParser::DATE_FORMAT);
        }
        if (!$account instanceof Account) {
            $account = $this->getAccount($account, $date);
        }
        if (!is_float($amount)) {
            $amount = $this->getAmount($amount);
        }
        if (!$recipient instanceof Recipient) {
            $recipient = $this->getRecipient($recipient);
        }
        if (!$category instanceof Category) {
            $category = $this->getCategory($category, $amount);
        }
        if (!is_int($state)) {
            $state = $this->getState($state);
        }
        if (!$payment instanceof Payment) {
            $payment = $this->getPayment($payment);
        }
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transaction->setDate($date);
        $transaction->setRecipient($recipient);
        $transaction->setMemo($this->getMemo($memo));
        $transaction->setAmount($amount);
        $transaction->setState($state);
        $transaction->setPayment($payment);
        $transaction->setCategory($category);

        $this->statistic->incTransaction($transaction);
        $this->entityManager->persist($transaction);

        return $transaction;
    }

    /**
     * Création d'une ligne d'opération boursière.
     *
     * @param Account|string  $account
     * @param DateTime|string $date
     * @param float|string    $amount
     * @param Stock|string    $stock
     * @param StockPosition   $operation
     * @param float|null      $volume
     * @param float|null      $price
     *
     * @return StockPortfolio
     */
    public function createStockPortfolio($account, $date, $amount, $stock, StockPosition $operation, ?float $volume, ?float $price): StockPortfolio
    {
        if (!$date instanceof DateTime) {
            $date = $this->getDateTime($date, QifParser::DATE_FORMAT);
        }
        if (!$account instanceof Account) {
            $account = $this->getAccount($account, $date);
        }
        if (!is_float($amount)) {
            $amount = $this->getAmount($amount);
        }
        if (!$stock instanceof Stock) {
            $stock = $this->getStock($stock);
        }
        // Calcul de la commission
        $fee = (null === $volume || null === $price) ? null : abs($amount) - ($volume * $price);
        // Création de l'opération boursière
        $portfolio = new StockPortfolio();
        $portfolio->setDate($date);
        $portfolio->setPosition($operation);
        $portfolio->setVolume($volume);
        $portfolio->setPrice($price);
        $portfolio->setFee($fee);
        $portfolio->setTotal($amount);
        $portfolio->setStock($stock);
        $portfolio->setAccount($account);

        $this->entityManager->persist($portfolio);

        return $portfolio;
    }

    /**
     * Retourne la date au format DateTime.
     *
     * @param string $date
     * @param string $format Format de base (ex d/m/Y)
     *
     * @return DateTime
     */
    public function getDateTime(string $date, string $format): DateTime
    {
        $objDate = DateTime::createFromFormat($format, $date);
        if (false === $objDate) {
            $objDate = new DateTime('1970-01-01');
        }

        return $objDate;
    }

    /**
     * Retourne le montant en virgule flotante.
     *
     * @param string $amount
     *
     * @return float
     */
    public function getAmount(string $amount): float
    {
        return (float) str_replace(',', '.', $amount);
    }

    /**
     * Retourne l'instition à chercher sinon la crée.
     *
     * @param string $searchInstitution
     *
     * @return Institution
     */
    public function getInstitution(string $searchInstitution): Institution
    {
        if ($this->institutions->offsetExists($searchInstitution)) {
            return $this->institutions->offsetGet($searchInstitution);
        }

        // Non trouvé alors on la crée
        $institution = $this->createInstitution($searchInstitution);
        $this->institutions->offsetSet($searchInstitution, $institution);

        return $institution;
    }

    /**
     * Créer une nouvelle institution.
     *
     * @param string $strInstitution
     *
     * @return Institution
     */
    public function createInstitution(string $strInstitution): Institution
    {
        $institution = new Institution();
        $institution->setName($strInstitution);
        $this->entityManager->persist($institution);

        return $institution;
    }

    /**
     * Retourne le compte à chercher sinon le crée.
     *
     * @param string        $searchAccount
     * @param DateTime|null $dateOpened
     *
     * @return Account
     */
    public function getAccount(string $searchAccount, ?DateTime $dateOpened = null): Account
    {
        if ($this->accounts->offsetExists($searchAccount)) {
            return $this->accounts->offsetGet($searchAccount);
        }

        // Non trouvé alors on le crée
        $account = $this->createAccount($searchAccount, $dateOpened);
        $this->accounts->offsetSet($searchAccount, $account);

        return $account;
    }

    /**
     * Créer un nouveau compte.
     *
     * @param string        $searchAccount
     * @param DateTime|null $dateOpened
     *
     * @return Account
     */
    public function createAccount(string $searchAccount, ?DateTime $dateOpened = null): Account
    {
        // Date par défaut (passage à l'euro)
        if (null === $dateOpened) {
            $dateOpened = new DateTime('2002-01-01');
        }

        [$strIntitution, $strAccount] = $this->splitString($searchAccount, ' ');

        // Non trouvé alors on le crée
        $account = new Account();
        $account->setInstitution($this->getInstitution($strIntitution));
        $account->setName($strAccount);
        $account->setType(new AccountType(11));
        $account->setOpenedAt($dateOpened);
        $this->entityManager->persist($account);
        $this->accounts->offsetSet($searchAccount, $account);

        return $account;
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

    /**
     * Retourne le bénéficiaire à chercher sinon le crée.
     *
     * @param string $searchRecipient
     *
     * @return Recipient
     */
    public function getRecipient(string $searchRecipient): Recipient
    {
        if ($this->recipients->offsetExists($searchRecipient)) {
            return $this->recipients->offsetGet($searchRecipient);
        }

        // Non trouvé alors on le crée
        $recipient = $this->createRecipient($searchRecipient);
        $this->recipients->offsetSet($searchRecipient, $recipient);

        return $recipient;
    }

    /**
     * Créer un nouveau bénéficiaire.
     *
     * @param string $strRecipient
     *
     * @return Recipient
     */
    public function createRecipient(string $strRecipient): Recipient
    {
        $recipient = new Recipient();
        $recipient->setName($strRecipient);
        $this->entityManager->persist($recipient);

        return $recipient;
    }

    /**
     * Retourne la catégorie à chercher sinon la crée.
     *
     * @param string $searchCategory
     * @param float  $amount         pour déterminer si recettes ou dépenses
     *
     * @return Category
     */
    public function getCategory(string $searchCategory, float $amount): Category
    {
        $searchCategory = sprintf('%s%s', (($amount > 0) ? '+' : '-'), $searchCategory);
        if ($this->catLevel2->offsetExists($searchCategory)) {
            return $this->catLevel2->offsetGet($searchCategory);
        }

        // Non trouvé alors on la crée
        $category = $this->createCategory($searchCategory);
        $this->catLevel2->offsetSet($searchCategory, $category);

        return $category;
    }

    /**
     * Créer une nouvelle catégorie.
     *
     * @param string $strCategory (+Categorie1:Categorie2) ou (-Categorie1:Categorie2)
     *
     * @return Category
     */
    public function createCategory(string $strCategory): Category
    {
        [$strCat1, $strCat2] = $this->splitString($strCategory, ':');

        // Chercher la catégorie de niveau 1
        if ($this->catLevel1->offsetExists($strCat1)) {
            $parent = $this->catLevel1->offsetGet($strCat1);
        } else {
            $parent = new Category();
            $parent->setName(substr($strCat1, 1));
            $parent->setLevel(1);
            $parent->setType(('+' === $strCat1[0]) ? Category::RECETTES : Category::DEPENSES);
            $this->entityManager->persist($parent);
            $this->catLevel1->offsetSet($strCat1, $parent);
        }

        // Non trouvée alors on crée la catégorie de niveau 2
        $category = new Category();
        $category->setParent($parent);
        $category->setName($strCat2);
        $category->setLevel(2);
        $category->setType($parent->getType());
        $this->entityManager->persist($category);

        return $category;
    }

    /**
     * Retourne le titre boursier à chercher sinon le crée.
     *
     * @param string $searchStock
     *
     * @return Stock
     */
    public function getStock(string $searchStock): Stock
    {
        if ($this->stocks->offsetExists($searchStock)) {
            return $this->stocks->offsetGet($searchStock);
        }

        // Non trouvé alors on le crée
        $stock = $this->createStock($searchStock);
        $this->stocks->offsetSet($searchStock, $stock);

        return $stock;
    }

    /**
     * Créer un nouveau titre boursier.
     *
     * @param string $strStock
     *
     * @return Stock
     */
    public function createStock(string $strStock): Stock
    {
        $stock = new Stock();
        $stock->setName($strStock);
        $stock->setSymbol($this->generateRandomString(10, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $stock->setCodeISIN('FR'.$this->generateRandomString(10, '0123456789'));
        $this->entityManager->persist($stock);

        return $stock;
    }

    /**
     * Retourne le mémo.
     *
     * @param string $memo
     *
     * @return string|null
     */
    public function getMemo(?string $memo): ?string
    {
        if ('' === $memo || '(null)' === $memo) {
            return null;
        }

        return $memo;
    }

    /**
     * Retourne le statut (rapprochement).
     *
     * @param string $state
     *
     * @return int
     */
    public function getState(string $state): int
    {
        if ('R' === $state) {
            return 1;
        }

        return 0;
    }

    /**
     * Découpe une chaine.
     *
     * @param string $string Chaine d'entrée à découper
     * @param string $needle Caractère de séparation
     *
     * @return array<string>
     */
    private function splitString(string $string, string $needle): array
    {
        if (false === strstr($string, $needle)) {
            $string = sprintf('%sInconnu:%s', $string[0], substr($string, 1));
        }
        $first = (string) strstr($string, $needle, true);
        $last = substr((string) strstr($string, $needle), 1);

        return [$first, $last];
    }

    /**
     * Retourne une chaine au hasard.
     *
     * @param int    $length
     * @param string $characters
     *
     * @return string
     */
    private function generateRandomString(int $length = 10, string $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        if ('' === $characters) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $charactersLength = strlen($characters);

        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
