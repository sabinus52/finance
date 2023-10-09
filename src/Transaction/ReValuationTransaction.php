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
use App\Form\ReValuationFormType;
use App\Values\Payment;
use App\Values\TransactionType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * Modèle de transaction d'une valorisation d'un capital.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class ReValuationTransaction extends TransactionModelAbstract implements TransactionModelInterface
{
    public function getFormClass(): string
    {
        return ReValuationFormType::class;
    }

    public function getFormOptions(): array
    {
        return [
            'filter' => [],
        ];
    }

    public function getFormTitle(): string
    {
        return 'une valorisation de capital';
    }

    protected function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::REVALUATION);
    }

    protected function getCategory(): ?Category
    {
        return $this->getCategoryByCode(Category::INCOME, Category::REVALUATION);
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
        return 'd\intérêt';
    }

    public function checkForm(FormInterface $form): bool
    {
        // Recherche si une transaction existe déjà
        $trt = $this->repository->findOneValorisation($this->transaction);

        if (null !== $trt) {
            $form->get('date')->addError(new FormError('Déjà existant'));

            return false;
        }

        return true;
    }
}
