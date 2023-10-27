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
use App\Values\Payment;

/**
 * Modèle de transaction des intérêts bancaires.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class InterestTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionStandardFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'account' => sprintf('acc.type >= %s AND acc.type <= %s', AccountType::EPARGNE_LIQUIDE, AccountType::EPARGNE_A_TERME * 10 + 9),
                '!fields' => ['account', 'category', 'payment', 'memo', 'project'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'un intérêt bancaire';
    }

    public function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::INCOME, Category::INTERET);
    }

    public function getPayment(): ?Payment
    {
        return new Payment(Payment::DEPOT);
    }

    public function getMessage(): string
    {
        return 'd\intérêt';
    }
}
