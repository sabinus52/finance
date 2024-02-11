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
use App\Entity\Institution;
use App\Entity\Project;
use App\Entity\Recipient;
use App\Entity\Stock;
use App\Entity\Vehicle;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\InstitutionRepository;
use App\Repository\ProjectRepository;
use App\Repository\RecipientRepository;
use App\Repository\StockRepository;
use App\Repository\VehicleRepository;
use App\Values\AccountType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Liste des données associées pour l'import.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class AssocDatas
{
    /**
     * Liste des organismes.
     */
    private \ArrayObject $institutions;

    /**
     * Liste des comptes.
     */
    private \ArrayObject $accounts;

    /**
     * Liste des bénéficiaires.
     */
    private \ArrayObject $recipients;

    /**
     * Liste des catégories de niveau 1.
     */
    private \ArrayObject $catLevel1;

    /**
     * Liste des catégories de niveau 2.
     */
    private \ArrayObject $catLevel2;

    /**
     * Liste des titres (actions).
     *
     * @var \ArrayObject
     */
    public $stocks;

    /**
     * Liste des projets.
     *
     * @var \ArrayObject
     */
    public $projects;

    /**
     * Liste des véhicules.
     *
     * @var \ArrayObject
     */
    public $vehicles;

    /**
     * Liste des datas nouvellement crées.
     *
     * @var array<mixed>
     */
    private $newCreated = [];

    /**
     * Constructeur.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->institutions = new \ArrayObject();
        $this->accounts = new \ArrayObject();
        $this->recipients = new \ArrayObject();
        $this->catLevel1 = new \ArrayObject();
        $this->catLevel2 = new \ArrayObject();
        $this->stocks = new \ArrayObject();
    }

    /**
     * Chargement des tableaux associatifs pour les liens avec les transactions.
     */
    public function load(): void
    {
        /** @var InstitutionRepository $repositoryInstitut */
        $repositoryInstitut = $this->entityManager->getRepository(Institution::class);
        $this->institutions = new \ArrayObject($repositoryInstitut->get4Import());

        /** @var AccountRepository $repositoryAccount */
        $repositoryAccount = $this->entityManager->getRepository(Account::class);
        $this->accounts = new \ArrayObject($repositoryAccount->get4Import());

        /** @var RecipientRepository $repositoryRecipient */
        $repositoryRecipient = $this->entityManager->getRepository(Recipient::class);
        $this->recipients = new \ArrayObject($repositoryRecipient->get4Import());

        /** @var CategoryRepository $repositoryCategory */
        $repositoryCategory = $this->entityManager->getRepository(Category::class);
        $this->catLevel1 = new \ArrayObject($repositoryCategory->get4ImportLevel1());
        $this->catLevel2 = new \ArrayObject($repositoryCategory->get4ImportLevel2());

        /** @var StockRepository $repositoryStock */
        $repositoryStock = $this->entityManager->getRepository(Stock::class);
        $this->stocks = new \ArrayObject($repositoryStock->get4Import());

        /** @var ProjectRepository $repositoryProject */
        $repositoryProject = $this->entityManager->getRepository(Project::class);
        $this->projects = new \ArrayObject($repositoryProject->get4Import());

        /** @var VehicleRepository $repositoryVehicle */
        $repositoryVehicle = $this->entityManager->getRepository(Vehicle::class);
        $this->vehicles = new \ArrayObject($repositoryVehicle->get4Import());
    }

    /**
     * Retourne l'instition à chercher sinon la crée.
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
     */
    public function createInstitution(string $strInstitution): Institution
    {
        $institution = new Institution();
        $institution->setName($strInstitution);
        $institution->setShortName($strInstitution);

        $this->entityManager->persist($institution);
        $this->newCreated[] = $institution;

        return $institution;
    }

    /**
     * Retourne le compte à chercher sinon le crée.
     *
     * @param \DateTime|null $dateOpened
     */
    public function getAccount(string $searchAccount, \DateTime $dateOpened = null): Account
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
     * @param \DateTime|null $dateOpened
     */
    public function createAccount(string $searchAccount, \DateTime $dateOpened = null): Account
    {
        // Date par défaut (passage à l'euro)
        if (!$dateOpened instanceof \DateTime) {
            $dateOpened = new \DateTime('2002-01-01');
        }

        [$strIntitution, $strAccount] = $this->splitString($searchAccount, ' ');

        // Non trouvé alors on le crée
        $account = new Account();
        $account->setInstitution($this->getInstitution($strIntitution));
        $account->setName($strAccount);
        $account->setShortName($strAccount);
        $account->setType(new AccountType(11));
        $account->setOpenedAt($dateOpened);

        $this->entityManager->persist($account);
        $this->newCreated[] = $account;

        return $account;
    }

    /**
     * Retoune un compte particulier en fonction de son type.
     *
     * @param int $type Numéro du type de compte
     */
    public function getAccountSpecial(int $type): ?Account
    {
        foreach ($this->accounts as $account) {
            /** @var Account $account */
            if ($type === $account->getType()->getValue()) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Retourne le bénéficiaire à chercher sinon le crée.
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
     * @param float $amount pour déterminer si recettes ou dépenses
     */
    public function getCategory(string $searchCategory, float $amount): Category
    {
        $searchCategory = sprintf('%s%s', ($amount > 0) ? '+' : '-', $searchCategory);
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
            $this->newCreated[] = $parent;
            $this->catLevel1->offsetSet($strCat1, $parent);
        }

        // Non trouvée alors on crée la catégorie de niveau 2
        $category = new Category();
        $category->setParent($parent);
        $category->setName($strCat2);
        $category->setLevel(2);
        $category->setType($parent->getType());

        $this->entityManager->persist($category);
        $this->newCreated[] = $category;

        return $category;
    }

    /**
     * Retourne le titre boursier à chercher sinon le crée.
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
     */
    public function createStock(string $strStock): Stock
    {
        $stock = new Stock();
        $stock->setName($strStock);
        $stock->setCodeISIN('FR'.$this->generateRandomString(10, '0123456789'));

        $this->entityManager->persist($stock);
        $this->newCreated[] = $stock;

        return $stock;
    }

    /**
     * Retourne le projet à chercher sinon le crée.
     */
    public function getProject(string $searchProject): Project
    {
        if ($this->projects->offsetExists($searchProject)) {
            return $this->projects->offsetGet($searchProject);
        }

        // Non trouvé alors on le crée
        $project = $this->createProject($searchProject);
        $this->projects->offsetSet($searchProject, $project);

        return $project;
    }

    /**
     * Créer un nouveau projet.
     */
    public function createProject(string $strProject): Project
    {
        $project = new Project();
        $project->setName($strProject);

        $this->entityManager->persist($project);
        $this->newCreated[] = $project;

        return $project;
    }

    /**
     * Retourne le véhicule à chercher sinon le crée.
     */
    public function getVehicle(string $searchVehicle): ?Vehicle
    {
        if ($this->vehicles->offsetExists($searchVehicle)) {
            return $this->vehicles->offsetGet($searchVehicle);
        }

        return null;
    }

    /**
     * Retoune la liste des nouveaux éléments créés.
     *
     * @return array<mixed>
     */
    public function getReportNewCreated(): array
    {
        $rows = [];
        foreach ($this->newCreated as $item) {
            if ($item instanceof Institution) {
                $rows[] = [$item->getName(), 'Organisme'];
            } elseif ($item instanceof Account) {
                $rows[] = [$item->getFullName(), 'Compte'];
            } elseif ($item instanceof Stock) {
                $rows[] = [$item->getName(), 'Titre'];
            } elseif ($item instanceof Category) {
                $rows[] = [$item->getFullName(), 'Categorie'];
            } elseif ($item instanceof Project) {
                $rows[] = [$item->getName(), 'Projet'];
            }
        }

        return $rows;
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
        if (!str_contains($string, $needle)) {
            $string = sprintf('%sInconnu:%s', $string[0], substr($string, 1));
        }
        $first = (string) strstr($string, $needle, true);
        $last = substr((string) strstr($string, $needle), 1);

        return [$first, $last];
    }

    /**
     * Retourne une chaine au hasard.
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
