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
use App\Values\StockPosition;
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
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
    }

    /**
     * Crée le modèle de transaction en fonction des données de la transaction (mode UPDATE).
     *
     * @param Transaction $transaction
     *
     * @return TransactionModelInterface
     */
    public function load(Transaction $transaction): TransactionModelInterface
    {
        // $this->transaction = $transaction;
        switch ($transaction->getType()->getValue()) {
            case TransactionType::STANDARD:
                if ($transaction->getCategory()->getCode()) {
                    $modelTransac = $this->createStandardByCategory($transaction->getCategory()->getCode());
                    break;
                }

                $modelTransac = $this->createStandardByType($transaction->getCategory()->getType());
                break;
            case TransactionType::TRANSFER:
                $modelTransac = $this->createTransferByCategory($transaction->getCategory()->getCode());
                break;
            case TransactionType::VEHICLE:
                $modelTransac = $this->createVehicle($transaction->getCategory()->getCode());
                break;
            case TransactionType::REVALUATION:
                $modelTransac = $this->createRevaluation();
                break;

            default:
                throw new Exception(sprintf('Type de transaction inconnu : %s. Valeurs possibles (%s)', $transaction->getType()->getValue(), implode(', ', TransactionType::getValues())));
        }
        $modelTransac->setTransaction($transaction);

        return $modelTransac;
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
            $modelTransac = new IncomeTransaction($this->entityManager);
        } else {
            $modelTransac = new ExpenseTransaction($this->entityManager);
        }
        $modelTransac->init();

        return $modelTransac;
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
                $modelTransac = new InterestTransaction($this->entityManager);
                break;
            case Category::TAXE:
                $modelTransac = new TaxTransaction($this->entityManager);
                break;
            default:
                throw new Exception(sprintf('Type de catégorie inconnu : %s.', $codeCat));
        }
        $modelTransac->init();

        return $modelTransac;
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
                $modelTransac = new VehicleFuelTransaction($this->entityManager);
                break;
            case Category::VEHICULEREPAIR:
                $modelTransac = new VehicleRepairTransaction($this->entityManager);
                break;
            case Category::VEHICULEFUNDING:
                $modelTransac = new VehicleFundingTransaction($this->entityManager);
                break;
            case Category::RESALE:
                $modelTransac = new VehicleResaleTransaction($this->entityManager);
                break;
            default:
                $modelTransac = new VehicleOtherTransaction($this->entityManager);
                break;
        }
        $modelTransac->init();

        return $modelTransac;
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
                $modelTransac = new TransferTransaction($this->entityManager);
                break;
            case Category::INVESTMENT:
                $modelTransac = new InvestmentTransaction($this->entityManager);
                break;
            case Category::REPURCHASE:
                $modelTransac = new RepurchaseTransaction($this->entityManager);
                break;
            default:
                throw new Exception(sprintf('Type de catégorie du transfert inconnu : %s. Valeurs possibles (%s)', $codeCat, implode(', ', Transfer::getCategoryValues())));
        }
        $modelTransac->init();

        return $modelTransac;
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
        $modelTransac = new ReValuationTransaction($this->entityManager);
        $modelTransac->init();

        if ($date) {
            $modelTransac->setDatas([
                'date' => $date->modify('last day of this month'),
                'amount' => 0,
            ]);
        }

        return $modelTransac;
    }
}
