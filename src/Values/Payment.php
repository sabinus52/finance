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
 * Liste des moyens de paiements.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Payment
{
    public const INTERNAL = 0; // Transaction interne : Virement, investissement
    public const CARTE = 1;
    public const CHEQUE = 2;
    public const ESPECE = 3;
    public const VIREMENT = 4;
    public const ELECTRONIC = 11;
    public const PAYPAL = 12;
    public const DEPOT = 21;
    public const PRELEVEMENT = 22;

    /**
     * Liste des environnements.
     *
     * @var array<array<string>>
     */
    protected static $payments = [
        self::INTERNAL => ['code' => 'INT', 'image' => 'internal', 'label' => 'Virement interne'],
        self::CARTE => ['code' => 'CB', 'image' => 'credit-card', 'label' => 'Carte de crédit/débit'],
        self::CHEQUE => ['code' => 'CHQ', 'image' => 'cheque', 'label' => 'Chèque'],
        self::ESPECE => ['code' => 'ESP', 'image' => 'money', 'label' => 'Espèces'],
        self::VIREMENT => ['code' => 'VIR', 'image' => 'virement', 'label' => 'Virement'],
        self::ELECTRONIC => ['code' => 'ETC', 'image' => 'electronique', 'label' => 'Paiement électronique'],
        self::PAYPAL => ['code' => 'PP', 'image' => 'paypal', 'label' => 'Paypal'],
        self::DEPOT => ['code' => 'DEP', 'image' => 'depot', 'label' => 'Dépôt'],
        self::PRELEVEMENT => ['code' => 'PVT', 'image' => 'prelevement', 'label' => 'Prélèvement'],
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
     * @return Payment[]
     */
    public static function getChoices(): array
    {
        $result = [];

        foreach (array_keys(self::$payments) as $payment) {
            if (self::INTERNAL === $payment) {
                continue;
            }
            $result[] = new self($payment);
        }

        return $result;
    }

    /**
     * Retourne la liste pour le filtre dans les Datatables.
     *
     * @param string $field
     *
     * @return array<string>
     */
    public static function getFilters(string $field = 'label'): array
    {
        $result = [];

        foreach (self::$payments as $payment => $array) {
            $result[$payment] = $array[$field];
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
        return self::$payments[$this->value]['label'];
    }

    public function getCode(): string
    {
        return self::$payments[$this->value]['code'];
    }

    public function getPathImage(): string
    {
        return sprintf('images/pay-%s.svg', self::$payments[$this->value]['image']);
    }

    public function __toString()
    {
        return $this->getLabel();
    }
}
