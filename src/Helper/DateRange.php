<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * Classe de gestion d'un interval prédéfini de date.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class DateRange
{
    public const LAST_30D = 'lastday:30';
    public const LAST_60D = 'lastday:60';
    public const LAST_90D = 'lastday:90';
    public const LAST_6M = 'lastmonth:5';
    public const LAST_12M = 'lastmonth:11';
    public const MONTH_CURRENT = 'month:current';
    public const MONTH_LAST = 'month:last';
    public const QUARTER_CURRENT = 'quarter:current';
    public const QUARTER_LAST = 'quarter:last';
    public const YEAR_CURRENT = 'year:current';
    public const YEAR_LAST = 'year:last';

    /**
     * @var array<mixed>
     */
    private static $ranges = [
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
     * Code de l'intervalle.
     *
     * @var string
     */
    private $range;

    /**
     * Date du jour.
     *
     * @var DateTimeImmutable
     */
    private $now;

    /**
     * Date de debut calculée.
     *
     * @var DateTimeImmutable
     */
    private $dateBegin;

    /**
     * Date de fin calculée.
     *
     * @var DateTimeImmutable
     */
    private $dateEnd;

    /**
     * Constructeur.
     *
     * @param string $range Code de l'instervalle à utiliser
     */
    public function __construct(string $range)
    {
        $this->range = $range;
        $this->now = new DateTimeImmutable();

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
     *
     * @return string
     */
    public function getDateStart(): string
    {
        return $this->dateBegin->format('Y-m-d');
    }

    /**
     * Retourne la date de début.
     *
     * @return string
     */
    public function getDateEnd(): string
    {
        return $this->dateEnd->format('Y-m-d');
    }

    /**
     * Calcule et détermine la date de début et de fin.
     */
    private function calculate(): void
    {
        [$action, $interval] = explode(':', $this->range);

        switch ($action) {
            case 'lastday':
                $result = $this->getLastDays($interval);
                break;
            case 'lastmonth':
                $result = $this->getLastMonths($interval);
                break;
            case 'month':
                $result = ('last' === $interval) ? $this->getLastMonth() : $this->getCurrentMonth();
                break;
            case 'quarter':
                $result = ('last' === $interval) ? $this->getLastQuarter() : $this->getCurrentQuarter();
                break;
            case 'year':
                $result = ('last' === $interval) ? $this->getLastYear() : $this->getCurrentYear();
                break;

            default:
                throw new Exception(sprintf('Type d\'intervalle inconnu pour calculer la date de début et de fin : %s', $action));
        }

        [$this->dateBegin, $this->dateEnd] = $result;
    }

    /**
     * Retourne les dates de début et fin des X derniers jours.
     *
     * @param string $interval
     *
     * @return DateTimeImmutable[]
     */
    private function getLastDays(string $interval): array
    {
        return [
            $this->now->sub(new DateInterval('P'.$interval.'D')),
            $this->now,
        ];
    }

    /**
     * Retourne les dates de début et fin des X derniers mois.
     *
     * @param string $interval
     *
     * @return DateTimeImmutable[]
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
     * @return DateTimeImmutable[]
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
     * @return DateTimeImmutable[]
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
     * @return DateTimeImmutable[]
     */
    private function getCurrentQuarter(): array
    {
        $startDate = new DateTimeImmutable();
        $endDate = new DateTimeImmutable();

        $month = $this->now->format('n');

        if ($month < 4) {
            $startDate = $this->now->modify('first day of january');
            $endDate = $this->now->modify('last day of march');
        } elseif ($month > 3 && $month < 7) {
            $startDate = $this->now->modify('first day of april');
            $endDate = $this->now->modify('last day of june');
        } elseif ($month > 6 && $month < 10) {
            $startDate = $this->now->modify('first day of july');
            $endDate = $this->now->modify('last day of september');
        } elseif ($month > 9) {
            $startDate = $this->now->modify('first day of october');
            $endDate = $this->now->modify('last day of december');
        }

        return [$startDate, $endDate];
    }

    /**
     * Retourne les dates de début et de fin du trimestre dernièr.
     *
     * @return DateTimeImmutable[]
     */
    private function getLastQuarter(): array
    {
        $startDate = new DateTimeImmutable();
        $endDate = new DateTimeImmutable();

        $last = $this->now->modify('- 1 month');
        $month = $last->format('n');

        if ($month < 4) {
            $startDate = $last->modify('first day of january');
            $endDate = $last->modify('last day of march');
        } elseif ($month > 3 && $month < 7) {
            $startDate = $last->modify('first day of april');
            $endDate = $last->modify('last day of june');
        } elseif ($month > 6 && $month < 10) {
            $startDate = $last->modify('first day of july');
            $endDate = $last->modify('last day of september');
        } elseif ($month > 9) {
            $startDate = $last->modify('first day of october');
            $endDate = $last->modify('last day of december');
        }

        return [$startDate, $endDate];
    }

    /**
     * Retourne les dates de début et de fin de l'année courante.
     *
     * @return DateTimeImmutable[]
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
     * @return DateTimeImmutable[]
     */
    private function getLastYear(): array
    {
        $last = $this->now->modify('- 1 year');

        return [
            $last->modify('first day of january'),
            $last->modify('last day of december'),
        ];
    }
}
