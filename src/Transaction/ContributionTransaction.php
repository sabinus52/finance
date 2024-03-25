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
 * Modèle de transaction des contributions sociales.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class ContributionTransaction extends TransactionModelAbstract implements TransactionModelInterface
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
        return 'une contribution sociale';
    }

    protected function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::EXPENSE, Category::TAXE_CSG);
    }

    protected function getPayment(): ?Payment
    {
        return new Payment(Payment::PRELEVEMENT);
    }

    public function getMessage(): string
    {
        return 'de CSG/CRDS';
    }
}
