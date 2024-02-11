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
class Payment implements \Stringable
{
    final public const INTERNAL = 0; // Transaction interne : Virement, investissement
    final public const CARTE = 1;
    final public const CHEQUE = 2;
    final public const ESPECE = 3;
    final public const VIREMENT = 4;
    final public const ELECTRONIC = 11;
    final public const PAYPAL = 12;
    final public const CADEO = 13;
    final public const DEPOT = 21;
    final public const PRELEVEMENT = 22;

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
        self::CADEO => ['code' => 'CDO', 'image' => 'cadeo', 'label' => 'Carte cadeaux'],
        self::DEPOT => ['code' => 'DEP', 'image' => 'depot', 'label' => 'Dépôt'],
        self::PRELEVEMENT => ['code' => 'PVT', 'image' => 'prelevement', 'label' => 'Prélèvement'],
    ];

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

    public function __construct(protected int $value)
    {
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

    public function __toString(): string
    {
        return $this->getLabel();
    }
}
