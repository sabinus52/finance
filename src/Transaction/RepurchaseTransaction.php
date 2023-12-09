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
use App\Entity\Recipient;
use App\Form\TransferFormType;
use App\Values\AccountType;
use App\Values\Payment;
use App\Values\TransactionType;

/**
 * Modèle de transaction d'un virement d'investissement.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class RepurchaseTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransferFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'source' => sprintf('(acc.type BETWEEN 50 AND 59 OR acc.type = %s)', AccountType::PEA_CAISSE),
                'target' => sprintf('acc.type <= 39 AND acc.type <> %s', AccountType::PEA_CAISSE),
                '!fields' => ['invest'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'un virement de rachat';
    }

    public function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::TRANSFER);
    }

    public function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::EXPENSE, Category::REPURCHASE);
    }

    protected function getPayment(): ?Payment
    {
        return new Payment(Payment::INTERNAL);
    }

    protected function getRecipient(): ?Recipient
    {
        return $this->findRecipientInternal();
    }

    public function getMessage(): string
    {
        return 'du virement';
    }
}
