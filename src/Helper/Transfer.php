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
use App\Values\Payment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des virements internes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Transfer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Type du transfert (VIREMENT | INVESTMENT).
     *
     * @var string
     */
    private $type;

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
     * Transaction avant la validation du formulaire.
     *
     * @var Transaction
     */
    private $before;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;
        $this->before = clone $transaction;

        $this->setTransaction($transaction);
    }

    /**
     * Affecte le type de transfert.
     *
     * @param string $type (VIREMENT | INVESTMENT)
     *
     * @return Transfer
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Retourne le type de transfert.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
            $this->setType($transaction->getCategory()->getCode());
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
        if (Category::INVESTMENT === $this->type || Category::CAPITALISATION === $this->type) {
            // Transaction créditeur
            $this->setCredit($target, Category::CAPITALISATION);
            // Transaction débiteur
            $this->setDebit($source, Category::INVESTMENT);
        } else {
            // Transaction créditeur
            $this->setCredit($target, Category::VIREMENT);
            // Transaction débiteur
            $this->setDebit($source, Category::VIREMENT);
        }
    }

    /**
     * Ajoute en base les transactions.
     */
    public function persist(): void
    {
        $this->entityManager->persist($this->debit);
        $this->entityManager->persist($this->credit);

        $this->update();
    }

    /**
     * Met à jour les transactions.
     */
    public function update(): void
    {
        $this->entityManager->flush();

        $helper = new Balance($this->entityManager);
        $helper->updateBalanceAfter($this->debit, $this->before->getDate());
        $helper->updateBalanceAfter($this->credit, $this->before->getDate());
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
     * @param Account $account
     * @param string  $catCode
     */
    private function setCredit(Account $account, string $catCode): void
    {
        TransactionHelper::setTransactionInternal($this->entityManager, $this->credit);
        $this->credit->setAccount($account);
        $this->credit->setAmount(abs($this->credit->getAmount()));
        $this->credit->setCategory($this->getCategory(Category::RECETTES, $catCode));
    }

    /**
     * Affecte les données à la transaction débiteur.
     *
     * @param Account $account
     * @param string  $catCode
     */
    private function setDebit(Account $account, string $catCode): void
    {
        TransactionHelper::setTransactionInternal($this->entityManager, $this->debit);
        $this->debit->setAccount($account);
        $this->debit->setDate($this->credit->getDate());
        $this->debit->setAmount($this->credit->getAmount() * -1);
        $this->debit->setCategory($this->getCategory(Category::DEPENSES, $catCode));
    }

    /**
     * Retourne une catégorie en fonction de son code.
     *
     * @param bool   $type
     * @param string $code
     *
     * @return Category
     */
    private function getCategory(bool $type, string $code): Category
    {
        return TransactionHelper::getCategoryByCode($this->entityManager, $type, $code);
    }
}
