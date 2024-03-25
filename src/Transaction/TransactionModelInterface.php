<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Entity\Account;
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
     */
    public function getFormTitle(): string;

    /**
     * Retourne le message lors de la validation du formulaire.
     */
    public function getMessage(): string;

    /**
     * Retourne la transation.
     */
    public function getTransaction(): Transaction;

    /**
     * Vérifie la validité du formulaire.
     */
    public function checkForm(FormInterface $form): bool;

    /**
     * Si c'est un virement.
     */
    public function isTransfer(): bool;

    /**
     * Initialisation des données de la transaction.
     */
    public function init(): self;

    /**
     * Affecte la transaction.
     */
    public function setTransaction(Transaction $transaction): self;

    /**
     * Affecte un compte à la transaction.
     */
    public function setAccount(Account $account): self;

    /**
     * Affecte des données à la transaction.
     *
     * @param array<mixed> $datas
     */
    public function setDatas(array $datas): self;

    /**
     * Ajoute la transaction en base.
     */
    public function insert(FormInterface $form = null): void;

    /**
     * Ajoute la transacion en base lors d'un import.
     *
     * @param array<mixed>|null $datas
     */
    public function insertModeImport(array $datas = null): void;

    /**
     * Mets à jour une transaction.
     */
    public function update(FormInterface $form = null): void;

    /**
     * Supprime une transaction.
     */
    public function delete(): void;
}
