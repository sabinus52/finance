<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Repository\CategoryRepository;
use App\Repository\RecipientRepository;
use App\Values\Payment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des virements internes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Transfer
{
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
     * @param EntityManagerInterface $manager
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;

        $this->setTransaction($transaction);
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
            $this->credit = $transaction;
            $this->credit->setPayment(new Payment(Payment::INTERNAL));
            $this->credit->setRecipient(new Recipient());
            $this->credit->setCategory(new Category());

            $this->debit = $this->createDebit();
        }

        return $this;
    }

    /**
     * Créer la transaction de débit.
     *
     * @return Transaction
     */
    private function createDebit(): Transaction
    {
        $debit = new Transaction();
        $this->credit->setTransfer($debit);
        $debit->setTransfer($this->credit);

        return $debit;
    }

    /**
     * Effectue le virement.
     *
     * @param Account $source
     * @param Account $target
     */
    public function makeTransfer(Account $source, Account $target): void
    {
        /** @var RecipientRepository $repoRpt */
        $repoRpt = $this->entityManager->getRepository(Recipient::class);
        /** @var CategoryRepository $repoCat */
        $repoCat = $this->entityManager->getRepository(Category::class);

        // Transaction créditeur
        $this->setCredit($target, $repoRpt->find(1), $repoCat->findTransfer(Category::RECETTES));
        // Transaction débiteur
        $this->setDebit($source, $repoRpt->find(1), $repoCat->findTransfer(Category::DEPENSES));
    }

    /**
     * Ajoute en base les transactions.
     */
    public function add(): void
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
     * Affecte les données à la transaction créditeur.
     *
     * @param Account   $account
     * @param Recipient $recipient
     * @param Category  $category
     *
     * @return Transfer
     */
    private function setCredit(Account $account, Recipient $recipient, Category $category): self
    {
        $this->credit->setAccount($account);
        $this->credit->setAmount(abs($this->credit->getAmount()));
        $this->credit->setPayment(new Payment(Payment::INTERNAL));
        $this->credit->setRecipient($recipient);
        $this->credit->setCategory($category);

        return $this;
    }

    /**
     * Affecte les données à la transaction débiteur.
     *
     * @param Account   $account
     * @param Recipient $recipient
     * @param Category  $category
     *
     * @return Transfer
     */
    private function setDebit(Account $account, Recipient $recipient, Category $category): self
    {
        $this->debit->setAccount($account);
        $this->debit->setDate($this->credit->getDate());
        $this->debit->setAmount($this->credit->getAmount() * -1);
        $this->debit->setPayment(new Payment(Payment::INTERNAL));
        $this->debit->setRecipient($recipient);
        $this->debit->setCategory($category);

        return $this;
    }
}
