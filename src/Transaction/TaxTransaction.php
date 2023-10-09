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
use App\Values\Payment;

/**
 * Modèle de transaction des taxes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class TaxTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionStandardFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                '!fields' => ['category', 'payment', 'memo', 'project'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'une taxe';
    }

    public function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::EXPENSE, Category::TAXE);
    }

    public function getPayment(): ?Payment
    {
        return new Payment(Payment::PRELEVEMENT);
    }

    public function getMessage(): string
    {
        return 'd\intérêt';
    }
}
