<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Values;

use App\Form\TransactionFormType;
use App\Form\TransactionVhFuelFormType;
use App\Form\TransactionVhMaintFormType;
use App\Form\TransactionVhOtherFormType;
use App\Form\TransferFormType;
use App\Form\ValorisationFormType;
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
    public const VIREMENT = 1;
    public const INVESTMENT = 10;
    public const RACHAT = 11;
    public const REVALUATION = 12;
    public const VH_OTHER = 20;
    public const VH_MAINT = 21;
    public const VH_FUEL = 22;

    /**
     * Liste des types de transaction.
     *
     * @var array<mixed>
     */
    private static $values = [
        self::STANDARD => ['form' => TransactionFormType::class, 'label' => '', 'code' => '', 'msg' => 'de la transaction'],
        self::VIREMENT => ['form' => TransferFormType::class, 'label' => '', 'code' => '', 'msg' => 'du virement'],
        self::INVESTMENT => ['form' => TransferFormType::class, 'label' => '', 'code' => '', 'msg' => 'de l\'investissement'],
        self::RACHAT => ['form' => TransferFormType::class, 'label' => '', 'code' => '', 'msg' => 'du rachat'],
        self::REVALUATION => ['form' => ValorisationFormType::class, 'label' => '', 'code' => '', 'msg' => 'de la valorisation'],
        self::VH_OTHER => ['form' => TransactionVhOtherFormType::class, 'label' => '', 'code' => '', 'msg' => 'de frais de véhicule'],
        self::VH_MAINT => ['form' => TransactionVhMaintFormType::class, 'label' => '', 'code' => '', 'msg' => 'd\'entretien/réparation'],
        self::VH_FUEL => ['form' => TransactionVhFuelFormType::class, 'label' => '', 'code' => '', 'msg' => 'de carburant'],
    ];

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
        if (!array_key_exists($value, self::$values)) {
            throw new Exception('La valeur "'.$value.'" est inconue, Valeur possible : '.implode(',', array_keys(self::$values)));
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
     * Retourne le label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return self::$values[$this->value]['label'];
    }

    /**
     * Retourne le formulaire.
     *
     * @return string
     */
    public function getForm(): string
    {
        return self::$values[$this->value]['form'];
    }

    /**
     * Retourne le libellé pour les messages.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return self::$values[$this->value]['msg'];
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
