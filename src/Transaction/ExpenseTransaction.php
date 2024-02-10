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
use App\Form\TransactionStandardFormType;
use App\Values\AccountType;

/**
 * Modèle de transaction des dépenses.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class ExpenseTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionStandardFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'account' => sprintf('acc.type <= %s', AccountType::COURANT * 10 + 9),
                'category' => sprintf('cat.type = %s', (int) (Category::EXPENSE)),
                '!fields' => ['account'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'une dépense';
    }

    public function getMessage(): string
    {
        return 'de débit';
    }
}
