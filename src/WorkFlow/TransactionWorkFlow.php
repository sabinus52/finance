<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * WorkFlow des transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TransactionWorkFlow
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Transaction en cours et valider par le formulaire.
     *
     * @var Transaction
     */
    private $transaction;

    /**
     * Transaction avant la validation du formulaire.
     *
     * @var Transaction
     */
    private $before;

    /**
     * @var Balance
     */
    private $balanceHelper;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;
        $this->repository = $this->entityManager->getRepository(Transaction::class); /** @phpstan-ignore-line */
        $this->before = clone $transaction;
        $this->balanceHelper = new Balance($this->entityManager);

        $this->transaction = $transaction;
        if ($transaction->getTransfer()) {
            // Dans le cas d'un virement, on prend la transaction de crédit
            if ($transaction->getAmount() < 0) {
                $this->transaction = $transaction->getTransfer();
            }
        }
    }

    /**
     * Ajoute la transaction en base.
     *
     * @param FormInterface|null $form
     */
    public function add(?FormInterface $form = null): void
    {
        // TODO verifier les données pour chaque type de transaction
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->persist();
        } else {
            $this->entityManager->persist($this->transaction);
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Mets à jour une transaction.
     *
     * @param FormInterface|null $form
     */
    public function update(?FormInterface $form = null): void
    {
        // TODO verifier les données pour chaque type de transaction
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->update();
        } else {
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Supprime une transaction.
     */
    public function remove(): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->remove();
        } else {
            $this->entityManager->remove($this->transaction);
            $this->entityManager->flush();
            $transfer = null;
        }
        $this->calculateBalance();
    }

    /**
     * Recalcule les soldes.
     */
    private function calculateBalance(): void
    {
        if ($this->transaction->getTransfer()) {
            $this->balanceHelper->updateBalanceAfter($this->transaction->getTransfer(), $this->before->getDate());
        }
        $this->balanceHelper->updateBalanceAfter($this->transaction, $this->before->getDate());
    }

    /**
     * Retourne la transaction en cours.
     *
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Retourne la valeur du type de la transaction.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->transaction->getType()->getValue();
    }

    /**
     * Retourne le formulaire à utiliser.
     *
     * @return string
     */
    public function getForm(): string
    {
        return $this->transaction->getType()->getForm();
    }

    /**
     * Si c'est un virement.
     *
     * @return bool
     */
    public function isTransfer(): bool
    {
        return TransactionType::VIREMENT === $this->getType() || TransactionType::INVESTMENT === $this->getType() || TransactionType::RACHAT === $this->getType();
    }

    /**
     * Vérifie les données du formulaire validé.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    public function checkForm(FormInterface $form): bool
    {
        if (TransactionType::REVALUATION === $this->transaction->getType()->getValue()) {
            return $this->checkFormValorisation($form);
        }

        return $this->checkFormStandard($form);
    }

    /**
     * Vérifie la validation du formulaire d'une transaction standard.
     *
     * @return bool
     */
    private function checkFormStandard(FormInterface $form): bool
    {
        if ($this->transaction->getAmount() > 0 && Category::DEPENSES === $this->transaction->getCategory()->getType()) {
            $form->get('category')->addError(new FormError(''));
            $form->get('amount')->addError(new FormError(''));

            return false;
        }
        if ($this->transaction->getAmount() < 0 && Category::RECETTES === $this->transaction->getCategory()->getType()) {
            $form->get('category')->addError(new FormError(''));
            $form->get('amount')->addError(new FormError(''));

            return false;
        }

        return true;
    }

    /**
     * Vérifie la validation du formulaire d'une transaction de valorisation de placement.
     *
     * @return bool
     */
    private function checkFormValorisation(FormInterface $form): bool
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
