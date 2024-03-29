<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\Account;
use App\Entity\Stock;
use App\Entity\StockPrice;
use App\Entity\StockWallet;
use App\Transaction\TransactionModelInterface;
use App\Transaction\TransactionModelRouter;
use App\Values\StockPosition;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Workflow d'une fusion d'un titre boursier.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class StockFusion
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionModelRouter
     */
    private $router;

    /**
     * @var Account
     */
    private $account;

    /**
     * Ancien titre boursier qui sera fusionné.
     *
     * @var Stock
     */
    private $oldStock;

    /**
     * @var float
     */
    private $oldVolume;

    /**
     * @var float
     */
    private $oldPrice;

    /**
     * Nouveau titre boursier fusionné.
     *
     * @var Stock
     */
    private $newStock;

    /**
     * @var float
     */
    private $newVolume;

    /**
     * @var float
     */
    private $newPrice;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Account                $account
     */
    public function __construct(EntityManagerInterface $manager, Account $account)
    {
        $this->entityManager = $manager;
        $this->account = $account;
        $this->router = new TransactionModelRouter($manager);
    }

    /**
     * Execute la fusion.
     */
    public function execute(): void
    {
        // Fusion du titre
        $this->oldStock->setFusionTo($this->newStock);

        // Création de la dernière cotation
        $this->createOldStockPrice();

        // Sate et montant de la transaction
        $date = $this->oldStock->getClosedAt();
        $amount = round($this->oldPrice * $this->oldVolume, 2);

        // Création de la transaction de vente
        $selling = $this->createTransactionSelling($date, $amount);
        $selling->insert();

        // Création de la transaction d'achat
        $buying = $this->createTransactionBuying($date, $amount);
        $buying->insert();
    }

    /**
     * Affecte les données de l'ancien titre qui sera fusionné.
     *
     * @param Stock $stock
     * @param float $price
     *
     * @return self
     */
    public function setOldStock(Stock $stock, float $price): self
    {
        $this->oldStock = $stock;
        $this->oldPrice = $price;

        // Récupère la ligne du portefeuille du titre pour récupérer le volume
        /** @var StockWallet $stockInWallet */
        $stockInWallet = $this->entityManager->getRepository(StockWallet::class)->findOneBy([
            'account' => $this->account,
            'stock' => $stock,
        ]);
        $this->oldVolume = $stockInWallet->getVolume();

        return $this;
    }

    /**
     * Affecte les données du nouveau titre fusionné.
     *
     * @param Stock|null  $stock    Nouveau titre si renseigné
     * @param string|null $name     Nom du nouveau titre à créer
     * @param string|null $codeIsin Code du nouveau titre à créer
     * @param float       $volume
     * @param float       $price
     *
     * @return self
     */
    public function setNewStock(?Stock $stock, ?string $name, ?string $codeIsin, float $volume, float $price): self
    {
        // Si le nouveau stock est déjà existant ou pas
        if ($stock) {
            $this->newStock = $stock;
        } else {
            $this->newStock = new Stock();
            $this->newStock
                ->setName($name)
                ->setCodeISIN($codeIsin)
            ;
            $this->entityManager->persist($this->newStock);
        }

        $this->newVolume = $volume;
        $this->newPrice = $price;

        return $this;
    }

    /**
     * Retourne le nouveau titre boursier.
     *
     * @return Stock
     */
    public function getNewStock(): Stock
    {
        return $this->newStock;
    }

    /**
     * Création de la cotation boursière de l'ancien titre.
     */
    private function createOldStockPrice(): void
    {
        // Recherche la dernière cotation
        /** @var StockPrice $lastPrice */
        $lastPrice = $this->entityManager->getRepository(StockPrice::class)->findOneLastPrice($this->oldStock); /** @phpstan-ignore-line */
        $date = clone $this->oldStock->getClosedAt();
        $date->modify('last day of this month');
        // Si la cotation à cette date existe, ne pas la rajouter à nouveau
        if ($lastPrice->getDate()->format('Y-m-d') === $date->format('Y-m-d')) {
            return;
        }

        $stockPrice = new StockPrice();
        $stockPrice->setStock($this->oldStock);
        $stockPrice->setDate($date);
        $stockPrice->setPrice($this->oldPrice);
        $this->entityManager->persist($stockPrice);
    }

    /**
     * Création de la transaction de vente de l'ancien titre.
     *
     * @param DateTime $date
     * @param float    $amount
     *
     * @return TransactionModelInterface
     */
    private function createTransactionSelling(DateTime $date, float $amount): TransactionModelInterface
    {
        $model = $this->router->createStock(new StockPosition(StockPosition::FUSION_SALE));
        $model->setAccount($this->account);
        $model->setDatas([
            'date' => $date,
            'amount' => $amount,
            'transactionStock' => [
                'stock' => $this->oldStock,
                'volume' => $this->oldVolume,
                'price' => $this->oldPrice,
            ],
        ]);

        return $model;
    }

    /**
     * Création de la transaction d'achat du nouveau titre.
     *
     * @param DateTime $date
     * @param float    $amount
     *
     * @return TransactionModelInterface
     */
    private function createTransactionBuying(DateTime $date, float $amount): TransactionModelInterface
    {
        $model = $this->router->createStock(new StockPosition(StockPosition::FUSION_BUY));
        $model->setAccount($this->account);
        $model->setDatas([
            'date' => $date,
            'amount' => $amount,
            'transactionStock' => [
                'stock' => $this->newStock,
                'volume' => $this->newVolume,
                'price' => $this->newPrice,
            ],
        ]);

        return $model;
    }
}
