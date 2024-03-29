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
use App\Entity\Category;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Values\TransactionType;
use DateTimeImmutable;

/**
 * Classe pour le calcul de la performance des placements.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Performance
{
    public const MONTH = 1;
    public const QUARTER = 3;
    public const YEAR = 12;

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
        $date = new DateTimeImmutable();
        $date = $date->modify('first day of this month');

        // Récupération des transactions du placement
        $this->transactions = $this->repository->findByAccount($this->account, [
            'range' => ['1970-01-01', $date->format('Y-m-d')],
        ]);
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
        // Initialise
        $results = $this->initializePerfItems($typePeriod);

        foreach ($this->transactions as $transaction) {
            // Crée la performance si elle n'existe pas
            $date = DateTimeImmutable::createFromMutable($transaction->getDate());
            $period = $this->getPeriod($date, $typePeriod);

            // On a investi durant la période
            if ($transaction->getCategory() && Category::INVESTMENT === $transaction->getCategory()->getCode()) {
                $results[$period]->addInvestment($transaction->getAmount());
            }

            // On a fait un rachat durant la période
            if ($transaction->getCategory() && Category::REPURCHASE === $transaction->getCategory()->getCode()) {
                $results[$period]->addRepurchase($transaction->getAmount());
            }

            // Si présence d'une valorisation du contrat
            if (TransactionType::REVALUATION === $transaction->getType()->getValue()) {
                $results[$period]->setValuation($transaction->getBalance());
            }
        }

        return $results;
    }

    /**
     * Initialise les items de performance de chaque période.
     *
     * @param int $typePeriod
     *
     * @return PerfItem[]
     */
    private function initializePerfItems(int $typePeriod): array
    {
        $results = [];
        $prevPerfItem = null;
        $start = DateTimeImmutable::createFromMutable($this->transactions[0]->getDate());
        $start = $start->modify('first day of this month');
        $end = new DateTimeImmutable();
        if (null !== $this->account->getClosedAt()) {
            $end = DateTimeImmutable::createFromMutable($this->account->getClosedAt());
        }

        while ($this->getPeriod($start, $typePeriod) <= $this->getPeriod($end, $typePeriod)) {
            $period = $this->getPeriod($start, $typePeriod);

            // Création de l'item
            $results[$period] = new PerfItem($typePeriod);
            $results[$period]->setPeriod($start)->setPrevious($prevPerfItem);

            // Passe à la période suivante
            $prevPerfItem = $results[$period];
            $start = $start->modify(sprintf('+%s month', $typePeriod));
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
     * @param DateTimeImmutable $date
     * @param int               $typePeriod
     *
     * @return string
     */
    private function getPeriod(DateTimeImmutable $date, int $typePeriod): string
    {
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
        if (empty($items)) {
            return [];
        }

        $result = [];
        $date = new DateTimeImmutable();

        // Recherche la dernière valorisation et sa date
        $idx = 0;
        do {
            $last = self::searchByPeriod($items, $date->modify(sprintf('- %s month', ++$idx)));
        } while (null === $last);
        $date = $date->modify(sprintf('- %s month', $idx));
        $last->calculate(); // Calcul du montant investi cumulé

        // Pour les X derniers mois
        $list = [1, 3, 6, 12, 24, 36, 60, 120];
        foreach ($list as $value) {
            $current = self::searchByPeriod($items, $date->modify(sprintf('- %s month', $value)));
            if ($current) {
                $current->calculate(); // Calcul du montant investi cumulé
            }
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
