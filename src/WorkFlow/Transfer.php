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
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manipulation des virements entre comptes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Transfer
{
    /**
     * Liste des catégories des transferts.
     *
     * @var array<mixed>
     */
    private static $categories = [
        Category::VIREMENT,
        Category::INVESTMENT,
        Category::REPURCHASE,
    ];

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
     */
    public function __construct(private readonly EntityManagerInterface $entityManager, Transaction $transaction)
    {
        $this->setTransaction($transaction);
    }

    /**
     * Ajoute en base les transactions.
     */
    public function persist(): void
    {
        $this->entityManager->persist($this->debit);
        $this->entityManager->persist($this->credit);
    }

    /**
     * Supprime le virement en base.
     */
    public function remove(): void
    {
        $this->debit->setAmount(0.0);
        $this->credit->setAmount(0.0);
        $this->debit->setTransfer(null);
        $this->credit->setTransfer(null);
        $this->entityManager->flush();

        $this->entityManager->remove($this->debit);
        $this->entityManager->remove($this->credit);
        $this->entityManager->flush();

        // Pour le calcul de la balance
        $this->credit->setTransfer($this->debit);
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
     */
    private function createCredit(Transaction $transaction): void
    {
        $this->credit = $transaction;
        $this->credit->setCategory($this->getCategory(Category::INCOME, $transaction->getCategory()->getCode()));
    }

    /**
     * Création de la transaction de débit.
     */
    private function createDebit(): void
    {
        $this->debit = clone $this->credit;
        $this->debit->setCategory($this->getCategory(Category::EXPENSE, $this->credit->getCategory()->getCode()));
    }

    /**
     * Effectue le virement.
     *
     * @param float|null $amountTarget Montant investi pour les placements
     */
    public function makeTransfer(Account $source, Account $target, float $amountTarget = null): void
    {
        if (null === $amountTarget) {
            $amountTarget = $this->credit->getAmount();
        }
        $this->debit->setAccount($source);
        $this->debit->setAmount(abs($this->credit->getAmount()) * -1);
        $this->debit->setDate($this->credit->getDate());
        $this->credit->setAccount($target);
        $this->credit->setAmount(abs($amountTarget));
    }

    /**
     * Retourne la catégorie à utiliser.
     *
     * @return Category
     */
    private function getCategory(bool $type, string $code): Category
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Category::class)->findOneByCode($type, $code);
    }

    /**
     * Retourne la liste des catégories des transferts.
     *
     * @return array<mixed>
     */
    public static function getCategoryValues(): array
    {
        return self::$categories;
    }
}
