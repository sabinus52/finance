<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Values;

use Exception;

/**
 * Classe statique sur les types de comptes.
 *
 * @author Olivier <sabinus52@gmail.com>
 */
class AccountType
{
    /**
     * Constantes des groupes de types de conptes.
     */
    public const COURANT = 1;
    public const EPARGNE_LIQUIDE = 2;
    public const EPARGNE_A_TERME = 3;
    public const EPARGNE_FINANCIERE = 4;
    public const EPARGNE_ASSURANCE_VIE = 5;

    public const ACC_CURRENT = 11;
    public const ACC_EPOSIT = 12;
    public const CREDIT_CARD = 13;
    public const PEA_CAISSE = 14;
    public const CEL = 21;
    public const LIVRET_A = 22;
    public const LDD = 23;
    public const LEP = 24;
    public const BOOKLET_JEUNE = 25;
    public const BOOKLET = 26;
    public const PEL = 31;
    public const CAT = 32;
    public const ACC_TITRES = 41;
    public const PEA_TITRES = 42;
    public const INVEST_ASSVIE = 51;
    public const INVEST_CONTRACT = 52;
    public const INVEST_PEP = 53;
    public const INVEST_PERP = 54;
    public const INVEST_PEE = 55;
    public const INVEST_OTHER = 59;

    /**
     * Liste des types de comptes.
     *
     * @var array<string>
     */
    private static $values = [
        self::ACC_CURRENT => 'Compte chèque',
        self::ACC_EPOSIT => 'Compte de dépôt',
        self::CREDIT_CARD => 'Carte de débit',
        self::PEA_CAISSE => 'PEA Espèces',
        self::CEL => 'CEL (compte d\'épargne logement)',
        self::LIVRET_A => 'Livret A',
        self::LDD => 'LDD (livret de développement durable)',
        self::LEP => 'LEP (livret d\'épargne populaire)',
        self::BOOKLET_JEUNE => 'Livret Jeune',
        self::BOOKLET => 'Livret',
        self::PEL => 'PEL (plan d\'épargne logement)',
        self::CAT => 'Compte à Terme',
        self::ACC_TITRES => 'Compte Titres',
        self::PEA_TITRES => 'PEA Titres',
        self::INVEST_ASSVIE => 'Assurance vie',
        self::INVEST_CONTRACT => 'Contrat de Capitalisation',
        self::INVEST_PEP => 'PEP (plan d\'épargne populaire)',
        self::INVEST_PERP => 'PERP (plan d\'épargne retraite populaire)',
        self::INVEST_PEE => 'PEE (plan d\'épargne entreprise)',
        self::INVEST_OTHER => 'Autre',
    ];

    /**
     * @var array<mixed>
     */
    public static $valuesGroupBy = [
        self::COURANT => [
            'menu' => 'Compte courant',
            'label' => 'Compte courant',
            'icon' => 'fas fa-credit-card',
            'values' => [self::ACC_CURRENT, self::ACC_EPOSIT, self::CREDIT_CARD, self::PEA_CAISSE],
        ],
        self::EPARGNE_LIQUIDE => [
            'menu' => 'Epargne liquide',
            'label' => 'Epargne liquide',
            'icon' => 'fas fa-piggy-bank',
            'values' => [self::CEL, self::LIVRET_A, self::LDD, self::LEP, self::BOOKLET_JEUNE, self::BOOKLET],
        ],
        self::EPARGNE_A_TERME => [
            'menu' => 'Epargne à terme',
            'label' => 'Epargne à terme',
            'icon' => 'fas fa-coins',
            'values' => [self::PEL, self::CAT],
        ],
        self::EPARGNE_FINANCIERE => [
            'menu' => 'Epargne financière',
            'label' => 'Epargne financière',
            'icon' => 'fas fa-landmark',
            'values' => [self::ACC_TITRES, self::PEA_TITRES],
        ],
        self::EPARGNE_ASSURANCE_VIE => [
            'menu' => 'Capitalisation',
            'label' => 'Assurance vie et capitalisation',
            'icon' => 'fas fa-wallet',
            'values' => [self::INVEST_ASSVIE, self::INVEST_CONTRACT, self::INVEST_PEP, self::INVEST_PERP, self::INVEST_PEE, self::INVEST_OTHER],
        ],
    ];

    /**
     * @var int
     */
    private $value;

    /**
     * Constructeur.
     */
    public function __construct(int $value)
    {
        if (!array_key_exists($value, self::$values)) {
            throw new Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', array_keys(self::$values)));
        }
        $this->value = $value;
    }

    /**
     * Retourne le label.
     */
    public function __toString()
    {
        return $this->getLabel();
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * Retourne la valeur.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Retourne le label.
     */
    public function getLabel(): string
    {
        return self::$values[$this->value];
    }

    /**
     * Retourne le code du type principal.
     */
    public function getTypeCode(): int
    {
        return (int) (floor($this->value / 10));
    }

    /**
     * Retourne le nom du type principal.
     */
    public function getTypeLabel(): string
    {
        return self::$valuesGroupBy[$this->getTypeCode()]['label'];
    }

    /**
     * Retourne l'iconedu type principal.
     */
    public function getTypeIcon(): string
    {
        return self::$valuesGroupBy[$this->getTypeCode()]['icon'];
    }

    /**
     * Retourne la liste des valeurs.
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return self::$values;
    }

    /**
     * Retourne la liste pour les formulaires de type "choices".
     *
     * @return array<mixed>
     */
    public static function getChoices(): array
    {
        $result = [];
        foreach (self::$valuesGroupBy as $group) {
            foreach ($group['values'] as $key) {
                $result[$group['label']][] = new self($key);
            }
        }

        return $result;
    }
}
