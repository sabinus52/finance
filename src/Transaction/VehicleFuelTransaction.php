<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Entity\Category;
use App\Form\TransactionVehicleFormType;
use App\Values\AccountType;
use App\Values\TransactionType;

/**
 * Modèle de transaction d'une dépense de carburant.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class VehicleFuelTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionVehicleFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'account' => sprintf('acc.type <= %s', AccountType::COURANT * 10 + 9),
                '!fields' => ['account', 'category', 'memo'],
                '!fieldsvh' => [],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'une dépense de carburant';
    }

    public function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::VEHICLE);
    }

    public function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::EXPENSE, Category::CARBURANT);
    }

    public function getMessage(): string
    {
        return 'de dépense de carburant';
    }
}
