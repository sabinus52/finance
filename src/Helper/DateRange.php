<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

/**
 * Classe de gestion d'un interval prédéfini de date.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class DateRange
{
    final public const LAST_30D = 'lastday:30';
    final public const LAST_60D = 'lastday:60';
    final public const LAST_90D = 'lastday:90';
    final public const LAST_6M = 'lastmonth:5';
    final public const LAST_12M = 'lastmonth:11';
    final public const MONTH_CURRENT = 'month:current';
    final public const MONTH_LAST = 'month:last';
    final public const QUARTER_CURRENT = 'quarter:current';
    final public const QUARTER_LAST = 'quarter:last';
    final public const YEAR_CURRENT = 'year:current';
    final public const YEAR_LAST = 'year:last';
    final public const NEXT_60D = 'nextday:60';

    /**
     * @var array<mixed>
     */
    private static array $ranges = [
        self::LAST_30D => '30 derniers jours',
        self::LAST_60D => '60 derniers jours',
        self::LAST_90D => '90 derniers jours',
        self::LAST_6M => '6 derniers mois',
        self::LAST_12M => '12 derniers mois',
        self::MONTH_CURRENT => 'Mois en cours',
        self::MONTH_LAST => 'Mois dernier',
        self::QUARTER_CURRENT => 'Trimestre en cours',
        self::QUARTER_LAST => 'Trimestre dernière',
        self::YEAR_CURRENT => 'Année en cours',
        self::YEAR_LAST => 'Année dernière',
    ];

    /**
     * Date du jour.
     */
    private readonly \DateTimeImmutable $now;

    /**
     * Date de debut calculée.
     *
     * @var \DateTimeImmutable
     */
    private $dateBegin;

    /**
     * Date de fin calculée.
     *
     * @var \DateTimeImmutable
     */
    private $dateEnd;

    /**
     * Constructeur.
     *
     * @param string $range code de l'intervalle
     * @param string $range Code de l'instervalle à utiliser
     */
    public function __construct(private readonly string $range)
    {
        $this->now = new \DateTimeImmutable();

        $this->calculate();
    }

    /**
     * Retourne la liste pour le ChoiceType des formulaires.
     *
     * @return array<string>
     */
    public static function getChoices(): array
    {
        $result = [];
        $result['Toutes les dates'] = '';

        foreach (self::$ranges as $key => $range) {
            $result[$range] = $key;
        }

        return $result;
    }

    /**
     *  Retourne les dates de début et fin calculées.
     *
     * @return array<string>
     */
    public function getRange(): array
    {
        return [$this->getDateStart(), $this->getDateEnd()];
    }

    /**
     * Retourne la date de fin.
     */
    public function getDateStart(): string
    {
        return $this->dateBegin->format('Y-m-d');
    }

    /**
     * Retourne la date de début.
     */
    public function getDateEnd(): string
    {
        return $this->dateEnd->format('Y-m-d');
    }

    /**
     * Retourne dans un tableau les jours dans un intervalle.
     *
     * @return array<mixed>
     */
    public function getDaysInRange(): array
    {
        $results = [];
        $indice = $this->dateBegin;
        while ($indice <= $this->dateEnd) {
            $results[$indice->format('Y-m-d')] = $indice->format('Y-m-d');
            $indice = $indice->modify('+ 1 day');
        }

        return $results;
    }

    /**
     * Calcule et détermine la date de début et de fin.
     */
    private function calculate(): void
    {
        [$action, $interval] = explode(':', $this->range);

        $result = match ($action) {
            'lastday' => $this->getLastDays($interval),
            'nextday' => $this->getNextDays($interval),
            'lastmonth' => $this->getLastMonths($interval),
            'month' => ('last' === $interval) ? $this->getLastMonth() : $this->getCurrentMonth(),
            'quarter' => ('last' === $interval) ? $this->getLastQuarter() : $this->getCurrentQuarter(),
            'year' => ('last' === $interval) ? $this->getLastYear() : $this->getCurrentYear(),
            default => throw new \Exception(sprintf('Type d\'intervalle inconnu pour calculer la date de début et de fin : %s', $action)),
        };

        [$this->dateBegin, $this->dateEnd] = $result;
    }

    /**
     * Retourne les dates de début et fin des X derniers jours.
     *
     * @return \DateTimeImmutable[]
     */
    private function getLastDays(string $interval): array
    {
        return [
            $this->now->sub(new \DateInterval('P'.$interval.'D')),
            $this->now,
        ];
    }

    /**
     * Retourne les dates de début et fin des X prochains jours.
     *
     * @return \DateTimeImmutable[]
     */
    private function getNextDays(string $interval): array
    {
        return [
            $this->now,
            $this->now->add(new \DateInterval('P'.$interval.'D')),
        ];
    }

    /**
     * Retourne les dates de début et fin des X derniers mois.
     *
     * @return \DateTimeImmutable[]
     */
    private function getLastMonths(string $interval): array
    {
        return [
            $this->now->modify(sprintf('- %s month', $interval))->modify('first day of this month'),
            $this->now->modify('last day of this month'),
        ];
    }

    /**
     * Retourne les dates de début et de fin du mois courant.
     *
     * @return \DateTimeImmutable[]
     */
    private function getCurrentMonth(): array
    {
        return [
            $this->now->modify('first day of this month'),
            $this->now->modify('last day of this month'),
        ];
    }

    /**
     * Retourne les dates de début et de fin du mois dernier.
     *
     * @return \DateTimeImmutable[]
     */
    private function getLastMonth(): array
    {
        $last = $this->now->modify('- 1 month');

        return [
            $last->modify('first day of this month'),
            $last->modify('last day of this month'),
        ];
    }

    /**
     * Retourne les dates de début et de fin du trimestre courant.
     *
     * @return \DateTimeImmutable[]
     */
    private function getCurrentQuarter(): array
    {
        return [
            self::getFirstDayOfQuarter($this->now),
            self::getLastDayOfQuarter($this->now),
        ];
    }

    /**
     * Retourne les dates de début et de fin du trimestre dernièr.
     *
     * @return \DateTimeImmutable[]
     */
    private function getLastQuarter(): array
    {
        $last = $this->now->modify('- 3 month');

        return [
            self::getFirstDayOfQuarter($last),
            self::getLastDayOfQuarter($last),
        ];
    }

    /**
     * Retourne les dates de début et de fin de l'année courante.
     *
     * @return \DateTimeImmutable[]
     */
    private function getCurrentYear(): array
    {
        return [
            $this->now->modify('first day of january'),
            $this->now->modify('last day of december'),
        ];
    }

    /**
     * Retourne les dates de début et de fin de l'année dernière.
     *
     * @return \DateTimeImmutable[]
     */
    private function getLastYear(): array
    {
        $last = $this->now->modify('- 1 year');

        return [
            $last->modify('first day of january'),
            $last->modify('last day of december'),
        ];
    }

    /**
     * Retourne le premier jour du trimestre.
     */
    public static function getFirstDayOfQuarter(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $month = $date->format('n');

        if ($month < 4) {
            return $date->modify('first day of january');
        }
        if ($month > 3 && $month < 7) {
            return $date->modify('first day of april');
        }
        if ($month > 6 && $month < 10) {
            return $date->modify('first day of july');
        }

        return $date->modify('first day of october');
    }

    /**
     * Retourne le dernier jour du trimestre.
     */
    public static function getLastDayOfQuarter(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $month = $date->format('n');

        if ($month < 4) {
            return $date->modify('last day of march');
        }
        if ($month > 3 && $month < 7) {
            return $date->modify('last day of june');
        }
        if ($month > 6 && $month < 10) {
            return $date->modify('last day of september');
        }

        return $date->modify('last day of december');
    }
}
