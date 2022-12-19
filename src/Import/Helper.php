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
use App\Entity\Stock;
use App\Entity\StockPortfolio;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Values\StockPosition;
use App\WorkFlow\Balance;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Classe d'aide pour l'omport des transactions venant d'un programme extérieur.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Helper
{
    /**
     * @var EntityManagerInterface
     */
    public $entityManager;

    /**
     * Statistiques de l'import.
     *
     * @var Statistic
     */
    public $statistic;

    /**
     * Liste des données associées (Account, Recepient, ...).
     *
     * @var AssocDatas
     */
    public $assocDatas;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        $this->statistic = new Statistic();
        $this->assocDatas = new AssocDatas($manager);
    }

    /**
     * Purge la table.
     *
     * @param string $table
     *
     * @return string|null
     */
    public function truncate(string $table): ?string
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        try {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
            $connection->executeStatement($platform->getTruncateTableSQL($table, false /* whether to cascade */));
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Création d'une transaction.
     *
     * @param QifItem $item
     *
     * @return Transaction
     */
    public function createTransaction(QifItem $item): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAccount($item->getAccount());
        $transaction->setType($item->getType());
        $transaction->setDate($item->getDate());
        $transaction->setRecipient($item->getRecipient());
        $transaction->setMemo($item->getMemo());
        $transaction->setAmount($item->getAmount());
        $transaction->setState($item->getState());
        $transaction->setPayment($item->getPayment());
        $transaction->setCategory($item->getCategory());

        $this->statistic->incTransaction($transaction);
        $this->entityManager->persist($transaction);

        return $transaction;
    }

    /**
     * Création d'une ligne d'opération boursière.
     *
     * @param QifItem        $item
     * @param Account|string $wallet
     * @param Stock|string   $stock
     * @param StockPosition  $operation
     * @param float|null     $volume
     * @param float|null     $price
     *
     * @return StockPortfolio
     */
    public function createStockPortfolio(QifItem $item, $wallet, $stock, StockPosition $operation, ?float $volume, ?float $price): StockPortfolio
    {
        if (!$wallet instanceof Account) {
            $wallet = $this->assocDatas->getAccount($wallet, $item->getDate());
        }
        if (!$stock instanceof Stock) {
            $stock = $this->assocDatas->getStock($stock);
        }
        // Calcul de la commission
        $fee = (null === $volume || null === $price) ? null : abs($item->getAmount()) - ($volume * $price);

        // Création de l'opération boursière
        $portfolio = new StockPortfolio();
        $portfolio->setDate($item->getDate());
        $portfolio->setPosition($operation);
        $portfolio->setVolume($volume);
        $portfolio->setPrice($price);
        $portfolio->setFee($fee);
        $portfolio->setTotal($item->getAmount());
        $portfolio->setStock($stock);
        $portfolio->setAccount($wallet);

        $this->entityManager->persist($portfolio);

        return $portfolio;
    }

    /**
     * Calcule le solde de tous les comptes.
     */
    public function calulateBalance(): void
    {
        $balance = new Balance($this->entityManager);

        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accounts = $repository->findAll();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $balance->updateBalanceAll($account);
            $this->statistic->setAccountData($account);
        }
    }
}
