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

    /**
     * Liste des types de comptes.
     *
     * @var array<string>
     */
    private static $values = [
        11 => 'Compte chèque',
        12 => 'Compte de dépôt',
        21 => 'CEL (compte d\'épargne logement)',
        22 => 'Livret A',
        23 => 'LDD (livret de développement durable)',
        24 => 'LEP (livret d\'épargne populaire)',
        25 => 'Livret Jeune',
        26 => 'Livret',
        31 => 'PEL (plan d\'épargne logement)',
        32 => 'Compte à Terme',
        41 => 'Compte Titres',
        42 => 'Compte Titres Espèces',
        43 => 'PEA Titres',
        44 => 'PEA Espèces',
        51 => 'Assurance vie',
        52 => 'Contrat de Capitalisation',
        53 => 'PEP (plan d\'épargne populaire)',
        54 => 'PERP (plan d\'épargne retraite populaire)',
        55 => 'PEE (plan d\'épargne entreprise)',
        59 => 'Autre',
    ];

    /**
     * @var array<mixed>
     */
    private static $valuesGroupBy = [
        self::COURANT => [
            'label' => 'Courant',
            'values' => [11, 12],
        ],
        self::EPARGNE_LIQUIDE => [
            'label' => 'Epargne liquide',
            'values' => [21, 22, 23, 24, 25, 26],
        ],
        self::EPARGNE_A_TERME => [
            'label' => 'Epargne à terme',
            'values' => [31, 32],
        ],
        self::EPARGNE_FINANCIERE => [
            'label' => 'Epargne financière',
            'values' => [41, 42, 43, 44],
        ],
        self::EPARGNE_ASSURANCE_VIE => [
            'label' => 'Aussurance vie et capitalisation',
            'values' => [51, 52, 53, 54, 55, 59],
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
     * Retourne le type principal.
     */
    public function getType(): string
    {
        return self::$valuesGroupBy[floor($this->value / 10)]['label'];
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
