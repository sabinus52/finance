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
use App\Transaction\TransactionModelRouter;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Workflow des transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final readonly class Workflow
{
    /**
     * Transaction avant la validation du formulaire.
     */
    private Transaction $before;

    private Balance $balance;

    /**
     * Constructeur.
     * Transaction en cours et valider par le formulaire.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Transaction $transaction
    ) {
        $this->before = clone $this->transaction;
        $this->balance = new Balance($this->entityManager);
    }

    /**
     * Ajoute la transaction en base.
     *
     * @param FormInterface|null $form
     */
    public function insert(FormInterface $form = null): void
    {
        if ($this->isTransfer()) {
            if ($form->has('purchase') && true === $form->get('purchase')->getData()) {
                // Cas particulier d'un rachat total
                $this->doRepurchaseTotal();
            }

            $transfer = new Transfer($this->entityManager, $this->transaction);
            if ($form->has('invest')) {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData(), $form->get('invest')->getData());
            } else {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            }
            $transfer->persist();

            $this->entityManager->flush();
        } else {
            $this->correctAmount();
            $this->entityManager->persist($this->transaction);
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Ajoute la transacion en base lors d'un import.
     *
     * @param array<mixed>|null $datas
     */
    public function insertModeImport(array $datas = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->makeTransfer($datas['source'], $datas['target'], $datas['invest']);
            $transfer->persist();
        } else {
            $this->correctAmount();
            $this->entityManager->persist($this->transaction);
        }
    }

    /**
     * Mets à jour une transaction.
     *
     * @param FormInterface|null $form
     */
    public function update(FormInterface $form = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            if ($form->has('invest')) {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData(), $form->get('invest')->getData());
            } else {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            }
            $this->entityManager->flush();
        } else {
            $this->correctAmount();
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Supprime une transaction.
     */
    public function delete(): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->remove();
        } else {
            $this->transaction->setAmount(0.0);
            $this->entityManager->remove($this->transaction);
            $this->entityManager->flush();
            $transfer = null;
        }
        $this->calculateBalance();
    }

    /**
     * Si c'est un virement.
     */
    public function isTransfer(): bool
    {
        return TransactionType::TRANSFER === $this->transaction->getType()->getValue();
    }

    /**
     * Corrige le montant en fonction si c'est une recette ou une dépense.
     */
    private function correctAmount(): void
    {
        $amount = $this->transaction->getAmount();
        if (Category::RECETTES === $this->transaction->getCategory()->getType()) {
            $this->transaction->setAmount(abs($amount));
        } else {
            $this->transaction->setAmount(abs($amount) * -1);
        }

        if (TransactionType::STOCKEXCHANGE === $this->transaction->getType()->getValue() && $this->transaction->getTransactionStock()->getPrice()) {
            $fee = abs($amount) - ($this->transaction->getTransactionStock()->getVolume() * $this->transaction->getTransactionStock()->getPrice());
            $this->transaction->getTransactionStock()->setFee(abs(round($fee, 2)));
        }
    }

    /**
     * Recalcule les soldes.
     */
    private function calculateBalance(): void
    {
        if ($this->isTransfer()) {
            $this->balance->updateBalanceAfter($this->transaction->getTransfer(), $this->before);
        }
        $this->balance->updateBalanceAfter($this->transaction, $this->before);
    }

    private function doRepurchaseTotal(): void
    {
        $this->transaction->setState(Transaction::STATE_RECONCILIED);
        $router = new TransactionModelRouter($this->entityManager);
        // Créer le valorisation finale avant le virement
        $revaluation1 = $router->createRevaluation();
        $revaluation1->setAccount($this->transaction->getAccount());
        $revaluation1->setDatas([
            'amount' => 0.0,
            'balance' => abs($this->transaction->getAmount()),
            'date' => $this->transaction->getDate(),
            'state' => Transaction::STATE_RECONCILIED,
        ]);
        $this->entityManager->persist($revaluation1->getTransaction());
        // Créer la transaction nulle de fin de mois
        $revaluation2 = $router->createRevaluation(clone $this->transaction->getDate());
        $revaluation2->setAccount($this->transaction->getAccount());
        $revaluation2->setDatas([
            'amount' => 0.0,
            'balance' => 0.0,
            'state' => Transaction::STATE_RECONCILIED,
        ]);
        $this->entityManager->persist($revaluation2->getTransaction());

        // Ferme le compte
        $this->closeAccount();
    }

    public function closeAccount(): void
    {
        // Date de fermeture
        $account = $this->transaction->getAccount();
        $account->setClosedAt($this->transaction->getDate());

        // Rapprochement de toutes les transactions
        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $repository->updateAllReconciliation($account);
    }
}
