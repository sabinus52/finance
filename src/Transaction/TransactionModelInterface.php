<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Entity\Transaction;
use Symfony\Component\Form\FormInterface;

/**
 * Inerfaces de modèles de transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
interface TransactionModelInterface
{
    /**
     * Retourne la classe du firmulaire.
     *
     * @return string
     */
    public function getFormClass(): string;

    /**
     * Retourne les options du formulaire.
     *
     * @return array<mixed>
     */
    public function getFormOptions(): array;

    /**
     * Retourne le titre du formulaire.
     *
     * @return string
     */
    public function getFormTitle(): string;

    /**
     * Retourne le message lors de la validation du formulaire.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Retourne la transation.
     *
     * @return Transaction
     */
    public function getTransaction(): Transaction;

    /**
     * Vérifie la validité du formulaire.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    public function checkForm(FormInterface $form): bool;

    /**
     * Si c'est un virement.
     *
     * @return bool
     */
    public function isTransfer(): bool;

    /**
     * Ajoute la transaction en base.
     *
     * @param FormInterface|null $form
     */
    public function add(?FormInterface $form = null): void;

    /**
     * Mets à jour une transaction.
     *
     * @param FormInterface|null $form
     */
    public function update(?FormInterface $form = null): void;

    /**
     * Supprime une transaction.
     */
    public function remove(): void;
}
