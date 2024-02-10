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
use App\Entity\Model;
use App\Entity\Transaction;
use App\Values\StockPosition;
use App\Values\TransactionType;
use App\WorkFlow\Transfer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Classe du router des différents modèles de transaction.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class TransactionModelRouter
{
    /**
     * Constructeur.
     */
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Crée le modèle de transaction en fonction des données de la transaction (mode UPDATE).
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
            case TransactionType::STOCKEXCHANGE:
                $modelTransac = $this->createStock($transaction->getTransactionStock()->getPosition());
                break;
            case TransactionType::REVALUATION:
                $modelTransac = $this->createRevaluation();
                break;

            default:
                throw new \Exception(sprintf('Type de transaction inconnu : %s. Valeurs possibles (%s)', $transaction->getType()->getValue(), implode(', ', TransactionType::getValues())));
        }
        $modelTransac->setTransaction($transaction);

        return $modelTransac;
    }

    /**
     * Crée le modèle de transaction standard en fonction de son type (recette ou dépense).
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
     * @return TransactionModelInterface
     */
    public function createStandardByCategory(string $codeCat): TransactionModelInterface
    {
        $modelTransac = match ($codeCat) {
            Category::INTERET => new InterestTransaction($this->entityManager),
            Category::TAXE_CSG => new ContributionTransaction($this->entityManager),
            Category::TAXE => new TaxTransaction($this->entityManager),
            default => throw new \Exception(sprintf('Type de catégorie inconnu : %s.', $codeCat)),
        };
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
        $modelTransac = match ($codeCat) {
            Category::CARBURANT => new VehicleFuelTransaction($this->entityManager),
            Category::VEHICULEREPAIR => new VehicleRepairTransaction($this->entityManager),
            Category::VEHICULEFUNDING => new VehicleFundingTransaction($this->entityManager),
            Category::RESALE => new VehicleResaleTransaction($this->entityManager),
            default => new VehicleOtherTransaction($this->entityManager),
        };
        $modelTransac->init();

        return $modelTransac;
    }

    public function createStock(?StockPosition $position): TransactionModelInterface
    {
        $modelTransac = match ($position->getValue()) {
            StockPosition::BUYING => new StockBuyingTransaction($this->entityManager),
            StockPosition::SELLING => new StockSellingTransaction($this->entityManager),
            StockPosition::DIVIDEND => new StockDividendTransaction($this->entityManager),
            StockPosition::FUSION_BUY => new StockFusionBuyingTransaction($this->entityManager),
            StockPosition::FUSION_SALE => new StockFusionSellingTransaction($this->entityManager),
            default => throw new \Exception(sprintf('Position bouorsière inconnu : %s.', $position)),
        };
        $modelTransac->init();

        return $modelTransac;
    }

    /**
     * Crée le modèle de transaction d'un virement en fonction de sa catégorie (virement, investissement ou rachat).
     *
     * @return TransactionModelInterface
     */
    public function createTransferByCategory(string $codeCat): TransactionModelInterface
    {
        $modelTransac = match ($codeCat) {
            Category::VIREMENT => new TransferTransaction($this->entityManager),
            Category::INVESTMENT => new InvestmentTransaction($this->entityManager),
            Category::REPURCHASE => new RepurchaseTransaction($this->entityManager),
            default => throw new \Exception(sprintf('Type de catégorie du transfert inconnu : %s. Valeurs possibles (%s)', $codeCat, implode(', ', Transfer::getCategoryValues()))),
        };
        $modelTransac->init();

        return $modelTransac;
    }

    /**
     * Crée le modèle de transaction de valorisation du capital en fin de mois.
     *
     * @param \DateTime|null $date
     *
     * @return TransactionModelInterface
     */
    public function createRevaluation(\DateTime $date = null): TransactionModelInterface
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

    /**
     * Crée le modèle de transaction à partir d'un modèle.
     *
     * @return TransactionModelInterface
     */
    public function createFromModel(Model $model): TransactionModelInterface
    {
        // Création en fonction de son type
        if ($model->getTransfer()) {
            $modelTransac = $this->createTransferByCategory($model->getCategory()->getCode());
        } elseif ($model->getVehicle()) {
            $modelTransac = $this->createVehicle(null);
            $modelTransac->setDatas([
                'transactionVehicle' => [
                    'vehicle' => $model->getVehicle(),
                ],
            ]);
        } else {
            $modelTransac = $this->createStandardByType($model->getAmount() > 0);
        }

        // Remplissage des données
        $modelTransac->setAccount($model->getAccount());
        $modelTransac->setDatas([
            'date' => \DateTime::createFromImmutable($model->getSchedule()->getDoAt()),
            'amount' => $model->getAmount(),
            'payment' => $model->getPayment(),
            'recipient' => $model->getRecipient(),
            'category' => $model->getCategory(),
            'memo' => $model->getMemo(),
        ]);

        return $modelTransac;
    }
}
