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
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Values\TransactionType;
use DateTimeImmutable;

/**
 * Classe pour le calcul de la performance des placements.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Performance
{
    public const MONTH = 1;
    public const QUARTER = 2;
    public const YEAR = 3;

    /**
     * Repository des transactions.
     *
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Contrat.
     *
     * @var Account
     */
    private $account;

    /**
     * Liste des transactions.
     *
     * @var Transaction[]
     */
    private $transactions;

    /**
     * Constructeur.
     *
     * @param TransactionRepository $repository
     * @param Account               $account
     */
    public function __construct(TransactionRepository $repository, Account $account)
    {
        $this->repository = $repository;
        $this->account = $account;

        // Récupération des transactions du placement
        $this->transactions = $this->repository->findByAccount($this->account);
    }

    /**
     * Affecte une liste de transactions autre s que ceux en base.
     *
     * @param Transaction[] $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    /**
     * Génération de la performance pour un type de période donné.
     *
     * @param int $typePeriod
     *
     * @return PerfItem[]
     */
    private function generate(int $typePeriod): array
    {
        $cumulInvest = $cumulValuation = 0;
        $prevPerfItem = null;
        $results = [];

        foreach ($this->transactions as $transaction) {
            // Crée la performance si elle n'existe pas
            $period = $this->getPeriod($transaction, $typePeriod);
            if (!array_key_exists($period, $results)) {
                $results[$period] = new PerfItem($typePeriod);
                $results[$period]->setPeriod($transaction->getDate())
                    ->setInvestCumul($cumulInvest)
                    ->setValuation($cumulValuation)
                    ->setPrevious($prevPerfItem)
                ;
            }

            // On a insvesti durant la période
            if (TransactionType::INVESTMENT === $transaction->getType()->getValue()) {
                $cumulInvest += $transaction->getAmount();
                $results[$period]->addInvest($transaction->getAmount());
            }

            $cumulValuation += $transaction->getAmount();
            $results[$period]->addValuation($transaction->getAmount());

            // Ecrase par la vrai Valorisation
            if (TransactionType::REVALUATION === $transaction->getType()->getValue()) {
                $cumulValuation = $transaction->getBalance();
                $results[$period]->setValuation($transaction->getBalance());
            }

            $prevPerfItem = $results[$period];
        }

        return $results;
    }

    /**
     * Retourne la performance glissante des X derniers mois.
     *
     * @return PerfItem[]
     */
    public function getBySlippery(): array
    {
        $items = $this->generate(self::MONTH);

        return self::getPerfSlipperyFromByMonth($items);
    }

    /**
     * Retourne la performance par mois.
     *
     * @return PerfItem[]
     */
    public function getByMonth(): array
    {
        return $this->generate(self::MONTH);
    }

    /**
     * Retoune la performance par trimestre.
     *
     * @return PerfItem[]
     */
    public function getByQuarter(): array
    {
        return $this->generate(self::QUARTER);
    }

    /**
     * Retoune la performance par année.
     *
     * @return PerfItem[]
     */
    public function getByYear(): array
    {
        return $this->generate(self::YEAR);
    }

    /**
     * Retourn la clé de la période en cours.
     *
     * @param Transaction $transaction
     * @param int         $typePeriod
     *
     * @return string
     */
    private function getPeriod(Transaction $transaction, int $typePeriod): string
    {
        $date = $transaction->getDate();
        if (self::YEAR === $typePeriod) {
            return $date->format('Y');
        }
        if (self::QUARTER === $typePeriod) {
            $quarter = floor(($date->format('n') + 2) / 3);

            return sprintf('%s-Q%s', $date->format('Y'), $quarter);
        }

        return $date->format('Y-m');
    }

    /**
     * Retourne la performance glissante des X derniers mois.
     *
     * @param PerfItem[] $items : Elements par mois
     *
     * @return PerfItem[]
     */
    public static function getPerfSlipperyFromByMonth(array $items): array
    {
        $result = [];
        $date = new DateTimeImmutable();

        // Recherche la dernière valorisation et sa date
        $idx = 0;
        do {
            $last = self::searchByPeriod($items, $date->modify(sprintf('- %s month', ++$idx)));
        } while (null === $last);
        $date = $date->modify(sprintf('- %s month', $idx));

        // Pour les X derniers mois
        $list = [1, 3, 6, 12, 36, 60, 120];
        foreach ($list as $value) {
            $current = self::searchByPeriod($items, $date->modify(sprintf('- %s month', $value)));
            $last->setPrevious($current);
            $result[$value] = clone $last;
        }

        return $result;
    }

    /**
     * Recherche et retourne une Perf d'une période donnée.
     *
     * @param PerfItem[]        $items
     * @param DateTimeImmutable $date
     *
     * @return PerfItem|null
     */
    public static function searchByPeriod(array $items, DateTimeImmutable $date): ?PerfItem
    {
        if (!isset($items[$date->format('Y-m')])) {
            return null;
        }

        return $items[$date->format('Y-m')];
    }
}
