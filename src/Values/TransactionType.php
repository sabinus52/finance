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
 * Classe statique sur les types de transactions.
 *
 * @author Olivier <sabinus52@gmail.com>
 */
class TransactionType
{
    /**
     * Constantes des types de transactions.
     */
    final public const STANDARD = 0;
    final public const STOCKEXCHANGE = 4;
    final public const VEHICLE = 5;
    final public const TRANSFER = 9;
    final public const REVALUATION = 7;

    /**
     * Liste des types de transaction.
     *
     * @var array<mixed>
     */
    private static array $values = [self::STANDARD, self::STOCKEXCHANGE, self::VEHICLE, self::TRANSFER, self::REVALUATION];

    private int $value;

    /**
     * Constructeur.
     */
    public function __construct(int $value)
    {
        /*if (!array_key_exists($value, self::$values)) {
            throw new Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', array_keys(self::$values)));
        }*/
        if (!in_array($value, self::$values, true)) {
            throw new \Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', self::$values));
        }
        $this->value = $value;
    }

    /**
     * Affecte la valeur.
     */
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
     * Retourne la liste des valeurs.
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return self::$values;
    }
}
