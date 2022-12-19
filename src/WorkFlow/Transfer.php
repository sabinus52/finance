<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manipulation des virements entre comptes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Transfer
{
    /**
     * Correspondance des catégories en fonction du type e virement.
     *
     * @var array<mixed>
     */
    private static $matches = [
        TransactionType::VIREMENT => [
            Category::RECETTES => Category::VIREMENT,
            Category::DEPENSES => Category::VIREMENT,
        ],
        TransactionType::INVESTMENT => [
            Category::RECETTES => Category::CAPITALISATION,
            Category::DEPENSES => Category::INVESTMENT,
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Transaction créditeur.
     *
     * @var Transaction
     */
    private $credit;

    /**
     * Transaction débiteur.
     *
     * @var Transaction
     */
    private $debit;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;

        $this->setTransaction($transaction);
    }

    /**
     * Ajoute en base les transactions.
     */
    public function persist(): void
    {
        $this->entityManager->persist($this->debit);
        $this->entityManager->persist($this->credit);
        $this->entityManager->flush();
    }

    /**
     * Met à jour les transactions.
     */
    public function update(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Supprime le virement en base.
     */
    public function remove(): void
    {
        $this->debit->setTransfer(null);
        $this->credit->setTransfer(null);
        $this->entityManager->flush();

        $this->entityManager->remove($this->debit);
        $this->entityManager->remove($this->credit);
        $this->entityManager->flush();
    }

    /**
     * Retourne la transaction du virement créditeur.
     *
     * @return Transaction
     */
    public function getCredit(): Transaction
    {
        return $this->credit;
    }

    /**
     * Retourne la transaction du virement débiteur.
     *
     * @return Transaction
     */
    public function getDebit(): Transaction
    {
        return $this->debit;
    }

    /**
     * Affecte les transactions de débit et de crédit en fonction de la transaction transmise.
     *
     * @param Transaction $transaction
     *
     * @return Transfer
     */
    public function setTransaction(Transaction $transaction): self
    {
        // Si on déjà en présence d'un virement
        if ($transaction->getTransfer()) {
            // En fonction du montant, on dispatch sur les transactions débit ou crédit
            if ($transaction->getAmount() < 0) {
                $this->debit = $transaction;
                $this->credit = $transaction->getTransfer();
            } else {
                $this->debit = $transaction->getTransfer();
                $this->credit = $transaction;
            }
        } else {
            // Sinon on crée le viremment
            $this->createCredit($transaction);
            $this->createDebit();
            $this->credit->setTransfer($this->debit);
            $this->debit->setTransfer($this->credit);
        }

        return $this;
    }

    /**
     * Création de la transaction de crédit avec la transaction courante du formulaire.
     *
     * @param Transaction $transaction
     */
    private function createCredit(Transaction $transaction): void
    {
        $this->credit = $transaction;
        $this->credit->setCategory($this->getCategory(Category::RECETTES));
    }

    /**
     * Création de la transaction de débit.
     */
    private function createDebit(): void
    {
        $this->debit = clone $this->credit;
        $this->debit->setCategory($this->getCategory(Category::DEPENSES));
    }

    /**
     * Effectue le virement.
     *
     * @param Account $source
     * @param Account $target
     */
    public function makeTransfer(Account $source, Account $target): void
    {
        $this->debit->setAccount($source);
        $this->debit->setAmount($this->credit->getAmount() * -1);
        $this->debit->setDate($this->credit->getDate());
        $this->credit->setAccount($target);
        $this->credit->setAmount(abs($this->credit->getAmount()));
    }

    /**
     * Retourne la catégorie à utiliser.
     *
     * @param bool $type
     *
     * @return Category
     */
    private function getCategory(bool $type): Category
    {
        $code = self::$matches[$this->credit->getType()->getValue()][$type];
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Category::class)->findOneByCode($type, $code);
    }
}
