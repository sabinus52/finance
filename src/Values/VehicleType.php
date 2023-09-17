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
 * Classe statique sur les types de véhicules.
 *
 * @author Olivier <sabinus52@gmail.com>
 */
class VehicleType
{
    /**
     * Constantes des groupes de types de conptes.
     */
    public const MOTO = 1;
    public const AUTO = 2;
    public const QUAD = 3;

    /**
     * Liste des types de véhicules.
     *
     * @var array<mixed>
     */
    private static $values = [
        self::AUTO => ['label' => 'Auto', 'icon' => 'fas fa-car'],
        self::MOTO => ['label' => 'Moto', 'icon' => 'fas fa-biking'],
        self::QUAD => ['label' => 'Quad', 'icon' => 'fas fa-truck-monster'],
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
        return self::$values[$this->value]['label'];
    }

    /**
     * Retourne le code du type principal.
     */
    public function getIcon(): string
    {
        return self::$values[$this->value]['icon'];
    }

    /**
     * Retourne la liste pour les formulaires de type "choices".
     *
     * @return array<mixed>
     */
    public static function getChoices(): array
    {
        $result = [];

        foreach (array_keys(self::$values) as $value) {
            $result[] = new self($value);
        }

        return $result;
    }
}
