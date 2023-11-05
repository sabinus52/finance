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
use App\Entity\StockPrice;
use App\Entity\StockWallet;
use App\Entity\Transaction;
use App\Repository\StockPriceRepository;
use App\Values\StockPosition;
use Doctrine\ORM\EntityManagerInterface;

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

/**
 * Workflow de la destion des portefeuilles boursiers.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Wallet
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Account
     */
    private $account;

    public function __construct(EntityManagerInterface $manager, Account $account)
    {
        $this->entityManager = $manager;
        $this->account = $account;
    }

    /**
     * Reconstruit le portefeuille à partir des transactions.
     *
     * @return StockWallet[]
     */
    public function reBuild(): array
    {
        // Portefeuille provisoire
        /** @var StockWallet[] $walletStocks */
        $walletStocks = [];
        // Liste des transactions sur les opérations boursières
        /** @var Transaction[] $transactions */
        $transactions = $this->entityManager->getRepository(Transaction::class)->findAllByWallet($this->account); /** @phpstan-ignore-line */

        // Construit le nouveau portefeuille
        foreach ($transactions as $transaction) {
            $id = $transaction->getTransactionStock()->getStock()->getId();
            if (!isset($walletStocks[$id])) {
                $walletStocks[$id] = new StockWallet();
                $walletStocks[$id]->setStock($transaction->getTransactionStock()->getStock());
                $walletStocks[$id]->setAccount($this->account);
            }
            if (StockPosition::BUYING === $transaction->getTransactionStock()->getPosition()->getValue()) {
                $walletStocks[$id]->addVolume($transaction->getTransactionStock()->getVolume());
            } elseif (StockPosition::SELLING === $transaction->getTransactionStock()->getPosition()->getValue()) {
                $walletStocks[$id]->subVolume($transaction->getTransactionStock()->getVolume());
            } elseif (StockPosition::FUSION_SALE === $transaction->getTransactionStock()->getPosition()->getValue()) {
                $walletStocks[$id]->subVolume($transaction->getTransactionStock()->getVolume());
            } elseif (StockPosition::FUSION_BUY === $transaction->getTransactionStock()->getPosition()->getValue()) {
                $walletStocks[$id]->addVolume($transaction->getTransactionStock()->getVolume());
            }
        }

        // Efface l'ancien portefeuille
        $this->entityManager->getRepository(StockWallet::class)->removeByAccount($this->account); /** @phpstan-ignore-line */

        // Sauvegarde le nouveau portefeuille
        foreach ($walletStocks as $id => $stock) {
            if ($stock->getVolume() <= 0) {
                unset($walletStocks[$id]);
                continue;
            }

            $stock->setPrice($this->getLastPrice($stock));
            $this->entityManager->persist($stock);
        }
        $this->entityManager->flush();

        return $walletStocks;
    }

    /**
     * Retourne le dernier cours trouvé du titre boursier.
     *
     * @param StockWallet $stock
     *
     * @return float
     */
    private function getLastPrice(StockWallet $stock): float
    {
        /** @var StockPriceRepository $repo */
        $repo = $this->entityManager->getRepository(StockPrice::class);
        $lastPrice = $repo->findOneLastPrice($stock->getStock());
        if (null === $lastPrice) {
            return 0;
        }

        return $lastPrice->getPrice();
    }
}
