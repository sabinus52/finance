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
 * Liste des catégories des projets.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ProjectCategory
{
    public const OTHER = 0;
    public const TRAVEL = 1;
    public const WORKS = 2;

    /**
     * Liste des environnements.
     *
     * @var array<array<string>>
     */
    protected static $categories = [
        self::OTHER => ['label' => 'Divers'],
        self::TRAVEL => ['label' => 'Voyages / Vacances'],
        self::WORKS => ['label' => 'Travaux d\'aménagement'],
    ];

    /**
     * Valeur de la catégorie.
     *
     * @var int
     */
    protected $value;

    /**
     * Retourne la liste pour le ChoiceType des formulaires.
     *
     * @return ProjectCategory[]
     */
    public static function getChoices(): array
    {
        $result = [];

        foreach (array_keys(self::$categories) as $category) {
            $result[] = new self($category);
        }

        return $result;
    }

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->getLabel();
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
        return self::$categories[$this->value]['label'];
    }
}
