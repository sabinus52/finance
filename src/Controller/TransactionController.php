<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Form\TransferType;
use App\Helper\Balance;
use App\Helper\Transfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des comptes et contrats.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TransactionController extends TransactionBase
{
    /**
     * Page d'accueil des comptes.
     *
     * @Route("/account/{id}/transactions", name="account__index")
     */
    public function index(Request $request, Account $account): Response
    {
        $formFilter = $this->createFormFilter(false);
        $formDelete = $this->createFormBuilder()->getForm();
        $formRecon = $this->createFormReconBalance($account);

        // Remplit le formulaire avec les données de la session
        $session = $request->getSession();
        $filter = $session->get('filter');
        if (null !== $filter) {
            foreach ($filter as $key => $value) {
                if ($formFilter->has($key)) {
                    $formFilter->get($key)->setData($value);
                }
            }
        }

        return $this->render('account/transactions.html.twig', [
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'filter' => $formFilter->createView(),
                'delete' => $formDelete->createView(),
                'recon' => $formRecon->createView(),
            ],
            'isReconcilied' => false,
        ]);
    }

    /**
     * @Route("/account/{id}/transactions/create", name="transaction__create", methods={"GET", "POST"})
     */
    public function createTransaction(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $form = $this->createForm(TransactionType::class, $transaction, [
            'action' => $this->generateUrl('transaction__create', ['id' => $account->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->checkValid($transaction)) {
                $form->get('category')->addError(new FormError(''));
                $form->get('amount')->addError(new FormError(''));
            } else {
                $entityManager->persist($transaction);
                $entityManager->flush();
                $helper = new Balance($entityManager);
                $helper->updateBalanceAfter($transaction);

                $this->addFlash('success', 'La création de la transaction a bien été prise en compte');

                return new Response('OK');
            }
        }

        return $this->renderForm('account/transaction-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/account/{id}/transfer/create", name="transfer__create", methods={"GET", "POST"})
     */
    public function createTransfer(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transfer = new Transfer($entityManager, $transaction);

        $form = $this->createForm(TransferType::class, $transaction, [
            'action' => $this->generateUrl('transfer__create', ['id' => $account->getId()]),
        ]);
        $form->get('source')->setData($account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->add();
            $helper = new Balance($entityManager);
            $helper->updateBalanceAfter($transfer->getDebit());
            $helper->updateBalanceAfter($transfer->getCredit());

            $this->addFlash('success', 'La création du virement a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/account/transactions/edit/{id}", name="transaction__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if (Transaction::STATE_RECONCILIED === $transaction->getState()) {
            return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                'message' => 'Impossible de modifier cette transaction !',
            ]);
        }

        if (null !== $transaction->getTransfer()) {
            if (Transaction::STATE_RECONCILIED === $transaction->getTransfer()->getState()) {
                return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                    'message' => 'Impossible de modifier ce virement !',
                ]);
            }

            return $this->updateTransfer($request, $transaction, $entityManager);
        }

        return $this->updateTransaction($request, $transaction, $entityManager);
    }

    private function updateTransaction(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction, [
            'action' => $this->generateUrl('transaction__edit', ['id' => $transaction->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->checkValid($transaction)) {
                $form->get('category')->addError(new FormError(''));
                $form->get('amount')->addError(new FormError(''));
            } else {
                $entityManager->flush();
                $helper = new Balance($entityManager);
                $helper->updateBalanceAfter($transaction);
                $this->addFlash('success', 'La modification de l\'opération a bien été prise en compte');

                return new Response('OK');
            }
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    private function updateTransfer(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $transfer = new Transfer($entityManager, $transaction);
        $transaction = $transfer->getCredit();

        $form = $this->createForm(TransferType::class, $transaction, [
            'action' => $this->generateUrl('transaction__edit', ['id' => $transaction->getId()]),
        ]);
        $form->get('source')->setData($transfer->getDebit()->getAccount()); // Compte débiteur
        $form->get('target')->setData($transfer->getCredit()->getAccount()); // Compte créditeur

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->update();
            $helper = new Balance($entityManager);
            $helper->updateBalanceAfter($transfer->getDebit());
            $helper->updateBalanceAfter($transfer->getCredit());

            $this->addFlash('success', 'La modification du virement a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    private function checkValid(Transaction $transaction): bool
    {
        if ($transaction->getAmount() > 0 && Category::DEPENSES === $transaction->getCategory()->getType()) {
            return false;
        }
        if ($transaction->getAmount() < 0 && Category::RECETTES === $transaction->getCategory()->getType()) {
            return false;
        }

        return true;
    }

    /**
     * @Route("/account/transactions/remove/{id}", name="transaction__remove", methods={"POST"})
     */
    public function remove(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if (Transaction::STATE_RECONCILIED === $transaction->getState()) {
            return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                'message' => 'Impossible de supprimer cette transaction !',
            ]);
        }
        if (null !== $transaction->getTransfer()) {
            if (Transaction::STATE_RECONCILIED === $transaction->getTransfer()->getState()) {
                return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                    'message' => 'Impossible de supprimer ce virement !',
                ]);
            }
        }

        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $helper = new Balance($entityManager);

            if (null === $transaction->getTransfer()) {
                $entityManager->remove($transaction);
                $entityManager->flush();
                $helper->updateBalanceAfter($transaction);
                $this->addFlash('success', 'La suppression de l\'opération a bien été prise en compte');
            } else {
                $transfer = new Transfer($entityManager, $transaction);
                $transfer->remove();
                $helper->updateBalanceAfter($transfer->getDebit());
                $helper->updateBalanceAfter($transfer->getCredit());
                $this->addFlash('success', 'La suppression du virement a bien été prise en compte');
            }

            return new Response('OK');
        }

        $this->addFlash('danger', 'Erreur lors de la suppression');

        return new Response('OK');
    }
}
