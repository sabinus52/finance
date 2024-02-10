<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Values;

/**
 * Liste des types de carburant.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Fuel implements \Stringable
{
    final public const PETROL = 1;
    final public const DESIEL = 2;
    final public const GAS = 3;
    final public const ELECTRIC = 4;
    final public const HYBRID = 5;

    /**
     * Liste des environnements.
     *
     * @var array<array<string>>
     */
    protected static $fuels = [
        self::PETROL => ['label' => 'Essence'],
        self::DESIEL => ['label' => 'Diesel'],
        self::GAS => ['label' => 'Gaz'],
        self::ELECTRIC => ['label' => 'Electrique'],
        self::HYBRID => ['label' => 'Hybride'],
    ];

    /**
     * Valeur du type.
     *
     * @var int
     */
    protected $value;

    /**
     * Retourne la liste pour le ChoiceType des formulaires.
     *
     * @return Fuel[]
     */
    public static function getChoices(): array
    {
        $result = [];

        foreach (array_keys(self::$fuels) as $fuel) {
            $result[] = new self($fuel);
        }

        return $result;
    }

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::$fuels[$this->value]['label'];
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }
}
