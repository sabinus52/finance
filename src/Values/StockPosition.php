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
 * Liste des opérations boursières.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class StockPosition
{
    public const BUYING = 1;
    public const SELLING = 2;
    public const FUSION_BUY = 3;
    public const FUSION_SALE = 4;
    public const DIVIDEND = 5;

    /**
     * Liste des environnements.
     *
     * @var array<array<string>>
     */
    protected static $positions = [
        self::BUYING => ['label' => 'Achat'],
        self::SELLING => ['label' => 'Vente'],
        self::FUSION_BUY => ['label' => 'Fusion fin'],
        self::FUSION_SALE => ['label' => 'Fusion début'],
        self::DIVIDEND => ['label' => 'Dividende'],
    ];

    /**
     * Valeur de l'environnement.
     *
     * @var int
     */
    protected $value;

    /**
     * Retourne la liste pour le ChoiceType des formulaires.
     *
     * @return StockPosition[]
     */
    public static function getChoices(): array
    {
        $result = [];

        foreach (array_keys(self::$positions) as $position) {
            $result[] = new self($position);
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
        return self::$positions[$this->value]['label'];
    }

    public function __toString()
    {
        return $this->getLabel();
    }
}
