<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Form\TransactionVehicleFormType;
use App\Values\TransactionType;

/**
 * Modèle de transaction d'une dépense d'entretien de véhicule.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class VehicleOtherTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionVehicleFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'category' => 'cat1.name IN (\'Auto / Moto\')',
                '!fields' => [],
                '!fieldsvh' => ['distance', 'volume'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'un frais sur véhicule';
    }

    public function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::VEHICLE);
    }

    public function getMessage(): string
    {
        return 'de frais de véhicule';
    }
}
