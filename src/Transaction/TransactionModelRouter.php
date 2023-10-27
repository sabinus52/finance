<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

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
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        $this->transaction = new Transaction();
    }

    /**
     * Crée le modèle de transaction en fonction des données de la transaction (mode UPDATE).
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
            case Category::VEHICULEFUNDING:
                $model = new VehicleFundingTransaction($this->entityManager, $this->transaction);
                break;
            case Category::RESALE:
                   $model = new VehicleResaleTransaction($this->entityManager, $this->transaction);
                break;
            default:
                $model = new VehicleOtherTransaction($this->entityManager, $this->transaction);
                break;
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

        return new ReValuationTransaction($this->entityManager, $this->transaction);
    }
}
