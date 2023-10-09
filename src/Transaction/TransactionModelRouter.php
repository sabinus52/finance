<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Values\TransactionType;
use App\WorkFlow\Transfer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Classe du router des différents modèles de transaction.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class TransactionModelRouter
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Transaction en cours et valider par le formulaire.
     *
     * @var Transaction
     */
    private $transaction;

    /**
     * Si nouvelle transaction pour son initialisation sinon récupération depuis la base.
     *
     * @var bool
     */
    private $isNew;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Account|null           $account
     */
    public function __construct(EntityManagerInterface $manager, ?Account $account = null)
    {
        $this->entityManager = $manager;
        $this->transaction = new Transaction();
        $this->transaction->setAccount($account);
        // Si le compte n'est pas renseigné alors on se ne trouve pas dans le cas de la création d'une transaction
        $this->isNew = (null === $account) ? false : true;
    }

    /**
     * Crée le modèle de transaction en fonction des données de la transaction.
     *
     * @param Transaction $transaction
     *
     * @return TransactionModelInterface
     */
    public function create(Transaction $transaction): TransactionModelInterface
    {
        $this->transaction = $transaction;
        switch ($transaction->getType()->getValue()) {
            case TransactionType::STANDARD:
                if ($transaction->getCategory()->getCode()) {
                    return $this->createStandardByCategory($transaction->getCategory()->getCode());
                }

                return $this->createStandardByType($transaction->getCategory()->getType());
            case TransactionType::TRANSFER:
                return $this->createTransferByCategory($transaction->getCategory()->getCode());
            case TransactionType::VEHICLE:
                return $this->createVehicle($transaction->getCategory()->getCode());
            case TransactionType::REVALUATION:
                return $this->createRevaluation();

            default:
                throw new Exception(sprintf('Type de transaction inconnu : %s. Valeurs possibles (%s)', $transaction->getType()->getValue(), implode(', ', TransactionType::getValues())));
        }
    }

    /**
     * Crée le modèle de transaction standard en fonction de son type (recette ou dépense).
     *
     * @param bool $type
     *
     * @return TransactionModelInterface
     */
    public function createStandardByType(bool $type): TransactionModelInterface
    {
        if (true === $type) {
            $model = new IncomeTransaction($this->entityManager, $this->transaction);
        } else {
            $model = new ExpenseTransaction($this->entityManager, $this->transaction);
        }

        if ($this->isNew) {
            $model->initTransaction();
        }

        return $model;
    }

    /**
     * Crée le modèle de transaction standard en fonction de sa catégorie.
     *
     * @param string $codeCat
     *
     * @return TransactionModelInterface
     */
    public function createStandardByCategory(string $codeCat): TransactionModelInterface
    {
        switch ($codeCat) {
            case Category::INTERET:
                $model = new InterestTransaction($this->entityManager, $this->transaction);
                break;
            case Category::TAXE:
                $model = new TaxTransaction($this->entityManager, $this->transaction);
                break;
            default:
                throw new Exception(sprintf('Type de catégorie inconnu : %s.', $codeCat));
        }

        if ($this->isNew) {
            $model->initTransaction();
        }

        return $model;
    }

    /**
     * Crée le modèle de transaction de frais de véhicule en fonction de sa catégorie.
     *
     * @param string|null $codeCat
     *
     * @return TransactionModelInterface
     */
    public function createVehicle(?string $codeCat): TransactionModelInterface
    {
        switch ($codeCat) {
            case Category::CARBURANT:
                $model = new VehicleFuelTransaction($this->entityManager, $this->transaction);
                break;
            case Category::VEHICULEREPAIR:
                $model = new VehicleRepairTransaction($this->entityManager, $this->transaction);
                break;
            default:
                $model = new VehicleOtherTransaction($this->entityManager, $this->transaction);
                break;
        }

        if ($this->isNew) {
            $model->initTransaction();
        }

        return $model;
    }

    /**
     * Crée le modèle de transaction d'un virement en fonction de sa catégorie (virement, investissement ou rachat).
     *
     * @param string $codeCat
     *
     * @return TransactionModelInterface
     */
    public function createTransferByCategory(string $codeCat): TransactionModelInterface
    {
        switch ($codeCat) {
            case Category::VIREMENT:
                $model = new TransferTransaction($this->entityManager, $this->transaction);
                break;
            case Category::INVESTMENT:
                $model = new InvestmentTransaction($this->entityManager, $this->transaction);
                break;
            case Category::REPURCHASE:
                $model = new RepurchaseTransaction($this->entityManager, $this->transaction);
                break;
            default:
                throw new Exception(sprintf('Type de catégorie du transfert inconnu : %s. Valeurs possibles (%s)', $codeCat, implode(', ', Transfer::getCategoryValues())));
        }

        if ($this->isNew) {
            $model->initTransaction();
        }

        return $model;
    }

    /**
     * Crée le modèle de transaction de valorisation du capital en fin de mois.
     *
     * @param DateTime|null $date
     *
     * @return TransactionModelInterface
     */
    public function createRevaluation(?DateTime $date = null): TransactionModelInterface
    {
        if ($date) {
            $this->transaction->setDate($date->modify('last day of this month'));
            $this->transaction->setAmount(0);
        }

        $model = new ReValuationTransaction($this->entityManager, $this->transaction);

        if ($this->isNew) {
            $model->initTransaction();
        }

        return $model;
    }
}
