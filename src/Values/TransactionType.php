<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Values;

use App\Entity\Transaction;
use Exception;

/**
 * Classe statique sur les types de transactions.
 *
 * @author Olivier <sabinus52@gmail.com>
 */
class TransactionType
{
    /**
     * Constantes des types de transactions.
     */
    public const STANDARD = 0;
    public const VEHICLE = 5;
    public const TRANSFER = 9;
    public const REVALUATION = 12;

    public const INCOME = 1;
    public const EXPENSE = 2;
    public const VIREMENT = 9;
    public const INVESTMENT = 10;
    public const RACHAT = 11;
    public const VH_OTHER = 20;
    public const VH_MAINT = 21;
    public const VH_FUEL = 22;

    /**
     * Liste des types de transaction.
     *
     * @var array<mixed>
     */
    private static $values = [self::STANDARD, self::VEHICLE, self::TRANSFER, self::REVALUATION, 1, 2, 10, 11, 12, 20, 21, 22];

    /**
     * @var int
     */
    private $value;

    /**
     * Constructeur.
     *
     * @param int $value
     */
    public function __construct(int $value)
    {
        /*if (!array_key_exists($value, self::$values)) {
            throw new Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', array_keys(self::$values)));
        }*/
        if (!in_array($value, self::$values, true)) {
            throw new Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', self::$values));
        }
        $this->value = $value;
    }

    /**
     * Affecte la valeur.
     *
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * Retourne la valeur.
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Retourne le type (dépenses ou recettes ou null = virement).
     *
     * @return bool|null
     */
    /*public function getType(): ?bool
    {
        return self::$values[$this->value]['type'];
    }*/

    /**
     * Retourne le label.
     *
     * @return string
     */
    /*public function getLabel(): string
    {
        return self::$values[$this->value]['label'];
    }*/

    /**
     * Retourne le formulaire.
     *
     * @return string
     */
    /*public function getForm(): string
    {
        return self::$values[$this->value]['form'];
    }*/

    /**
     * Retourne le libellé pour les messages.
     *
     * @return string
     */
    /*public function getMessage(): string
    {
        return self::$values[$this->value]['msg'];
    }*/

    /**
     * Retourne la liste des valeurs.
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return self::$values;
    }
}
