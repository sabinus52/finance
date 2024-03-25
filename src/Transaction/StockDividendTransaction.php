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
use App\Form\TransactionStockFormType;
use App\Values\AccountType;
use App\Values\Payment;
use App\Values\StockPosition;
use App\Values\TransactionType;

/**
 * Modèle de transaction d'une vente d'un titre.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class StockDividendTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return TransactionStockFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [
                'account' => sprintf('acc.type >= %s AND acc.type <= %s', AccountType::EPARGNE_FINANCIERE * 10, AccountType::EPARGNE_FINANCIERE * 10 + 9),
                '!fields' => ['account', 'recipient', 'category', 'payment', 'memo', 'project'],
                '!fieldstk' => ['account', 'position', 'fee', 'volume', 'price'],
            ],
        ];
    }

    public function getFormTitle(): string
    {
        return 'une vente de titre';
    }

    protected function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::STOCKEXCHANGE);
    }

    protected function getPayment(): ?Payment
    {
        return new Payment(Payment::INTERNAL);
    }

    protected function getRecipient(): ?Recipient
    {
        return $this->findRecipientInternal();
    }

    protected function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::INCOME, Category::DIVIDENDES);
    }

    protected function getPosition(): ?StockPosition
    {
        return new StockPosition(StockPosition::DIVIDEND);
    }

    public function getMessage(): string
    {
        return "d'une vente de titre";
    }
}
