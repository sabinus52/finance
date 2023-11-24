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
use App\Entity\Category;
use App\Entity\StockPrice;
use App\Entity\StockWallet;
use App\Entity\Transaction;
use App\Values\TransactionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

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

    /**
     * Historique du portefeuille.
     *
     * @var WalletHistory[]
     */
    private $histories;

    public function __construct(EntityManagerInterface $manager, Account $account)
    {
        $this->entityManager = $manager;
        $this->account = $account;
        $this->histories = [];
    }

    /**
     * Retourne le portefeuille courant.
     *
     * @return WalletHistory
     */
    public function getWallet(): WalletHistory
    {
        if (empty($this->histories)) {
            $result = $this->entityManager->getRepository(StockWallet::class)->findBy(['account' => $this->account]);

            $wallet = new WalletHistory();
            $wallet->setWallet($result);

            return $wallet;
        }

        return end($this->histories);
    }

    /**
     * Retourne les opérations booursières.
     *
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Transaction::class)->findAllByWallet($this->account);
    }

    /**
     * Retourne les transactions de versement et de réévaluations.
     *
     * @return Transaction[]
     */
    public function getTransactionHistories(): array
    {
        if (empty($this->histories)) {
            $this->buildWallets();
        }

        $transactions = [];
        $lastBalance = $lastInvest = 0.0;
        $category = $this->entityManager->getRepository(Category::class)->findOneByCode(Category::INCOME, Category::INVESTMENT); /** @phpstan-ignore-line */

        // Pour chaque portefeuille
        foreach ($this->histories as $item) {
            $invest = $item->getAmountInvest();
            if ($invest !== $lastInvest) {
                $transaction = new Transaction();
                $transaction
                    ->setDate($item->getDate())
                    ->setTypeValue(TransactionType::STANDARD)
                    ->setAmount($invest - $lastInvest)
                    ->setCategory($category)
                ;
                $lastInvest = $invest;
                $transactions[] = $transaction;
            }

            $balance = $item->getValorisation();
            if ($balance > 0.0) {
                // Ne sauvegarde pas les valorisation à 0
                $transaction = new Transaction();
                $transaction
                    ->setDate($item->getDate())
                    ->setTypeValue(TransactionType::REVALUATION)
                    ->setAmount($balance - $lastBalance)
                    ->setBalance($balance)
                ;
                $lastBalance = $balance;
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * Reconstruit le portefeuille à partir des transactions.
     *
     * @return WalletHistory[]
     */
    public function buidAndSaveWallet(): array
    {
        $this->buildWallets();

        $wallet = end($this->histories);
        if ($wallet) {
            $this->saveCurrentWallet($wallet);
        }

        return $this->histories;
    }

    /**
     * Reconstruit le portefeuille à partir des transactions.
     */
    private function buildWallets(): void
    {
        // Liste des transactions sur les opérations boursières
        $transactions = $this->getTransactions();
        /** @var DateTime $lastDate */
        $lastDate = null;

        // Construit les portefeuilles par mois en fonction des transactions
        foreach ($transactions as $transaction) {
            $month = $transaction->getDate()->format('Y-m');

            // Premier Portefeuille
            if (null === $lastDate) {
                $this->histories[$month] = new WalletHistory();
                $this->histories[$month]->setDate(clone $transaction->getDate());
                $lastDate = $transaction->getDate();
            }

            // Créé les portefeuilles intermédiaires où il n'y a pas eu de transaction
            $this->addIntermediateHistories(clone $lastDate, $transaction->getDate());

            // Nouveau portefeuille trouvé à créer
            if (!isset($this->histories[$month])) {
                $this->createWalletHistory($transaction->getDate(), $this->histories[$lastDate->format('Y-m')]);
            }

            // Traite la transaction en cours
            $this->histories[$month]->addPosition($transaction);

            $lastDate = $transaction->getDate();
        }

        // Créé jusqu'à ce jour
        $this->addIntermediateHistories(clone $lastDate, new DateTime());

        $this->setAllPriceWallet();
    }

    /**
     * Créé des portefeuille intermédiare entre 2 dates avec comme portefeuille de référence la date de début.
     *
     * @param DateTime $start
     * @param DateTime $end
     */
    private function addIntermediateHistories(DateTime $start, DateTime $end): void
    {
        $start->modify('first day of this month');
        $refDate = clone $start;
        $start->modify('+ 1 month');
        while ($start->format('Y-m') < $end->format('Y-m')) {
            $this->createWalletHistory($start, $this->histories[$refDate->format('Y-m')]);
            $start->modify('+ 1 month');
        }
    }

    /**
     * Créé un nouveau portefeuille d'une date donnée à partir d'un autre portefeuille.
     *
     * @param DateTime      $date      Date du nouveau portefeuille
     * @param WalletHistory $refWallet Porefeuille de référence à partir duquel le nouveau sera créé
     *
     * @return WalletHistory
     */
    private function createWalletHistory(DateTime $date, WalletHistory $refWallet): WalletHistory
    {
        $month = $date->format('Y-m');
        $this->histories[$month] = clone $refWallet;
        $this->histories[$month]->setDate(clone $date);

        return $this->histories[$month];
    }

    /**
     * Sauvegarde en base le dernier portefeuille en cours.
     *
     * @param WalletHistory $wallet
     */
    private function saveCurrentWallet(WalletHistory $wallet): void
    {
        // Efface l'ancien portefeuille
        $this->entityManager->getRepository(StockWallet::class)->removeByAccount($this->account); /** @phpstan-ignore-line */
        foreach ($wallet as $item) {
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();
    }

    /**
     * Affecte le cours de chaque titre sur toutes l'historique des portefeuilles.
     */
    private function setAllPriceWallet(): void
    {
        $pricesByStockByMonth = $this->entityManager->getRepository(StockPrice::class)->findGroupByStockDate(); /** @phpstan-ignore-line */

        // Pour chaque portefeuille
        foreach ($this->histories as $wallet) {
            // Pour chaque titre du portefeuille
            /** @var StockWallet $item */
            foreach ($wallet as $item) {
                $date = clone $wallet->getDate();
                $stockId = $item->getStock()->getId();

                // Vérifie si un cours existe à cette date pour ce titre
                if (isset($pricesByStockByMonth[$stockId][$date->format('Y-m')])) {
                    $item->setPrice($pricesByStockByMonth[$stockId][$date->format('Y-m')]);
                    $item->setPriceDate($date);
                } else {
                    $lastMonth = clone $date;
                    $lastMonth->modify('first day of this month')->modify('- 1 month');
                    if (isset($pricesByStockByMonth[$stockId][$lastMonth->format('Y-m')])) {
                        $item->setPrice($pricesByStockByMonth[$stockId][$lastMonth->format('Y-m')]);
                        $item->setPriceDate($lastMonth);
                    } else {
                        $item->setPrice(0.0);
                    }
                }
            }
        }
    }
}
