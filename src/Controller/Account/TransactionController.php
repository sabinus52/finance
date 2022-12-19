<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\WorkFlow\TransactionHelper;
use App\WorkFlow\TransactionWorkFlow;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends BaseController
{
    /**
     * Création d'une transaction.
     *
     * @Route("/account/{id}/transactions/create", name="transaction__create", methods={"GET", "POST"})
     */
    public function createTransaction(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $helper = new TransactionHelper($entityManager);

        $transaction = $helper->createStandard();
        $transaction->setAccount($account);

        return $this->create($entityManager, $request, $transaction);
    }

    /**
     * Création d'un virement.
     *
     * @Route("/account/{id}/transfer/create", name="transfer__create", methods={"GET", "POST"})
     */
    public function createVirement(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $helper = new TransactionHelper($entityManager);

        $transaction = $helper->createVirement();
        $transaction->setAccount($account);

        return $this->create($entityManager, $request, $transaction);
    }

    /**
     * Création d'un investissement.
     *
     * @Route("/account/{id}/invest/create", name="invest__create", methods={"GET", "POST"})
     */
    public function createInvestment(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $helper = new TransactionHelper($entityManager);

        $transaction = $helper->createInvestment();
        $transaction->setAccount($account);

        return $this->create($entityManager, $request, $transaction);
    }

    /**
     * Création d'une valorisation sur un placement.
     *
     * @Route("/account/{id}/capital/create", name="capital__create", methods={"GET", "POST"})
     */
    public function createValorisation(Request $request, Account $account, EntityManagerInterface $entityManager, TransactionRepository $repository): Response
    {
        $helper = new TransactionHelper($entityManager);

        // Recherche la dernière transaction de valorisation
        $last = $repository->findOneLastValorisation($account);
        $date = new DateTime();
        if (null !== $last) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        $transaction = $helper->createValorisation();
        $transaction->setAccount($account);
        $transaction->setDate($date->modify('last day of this month'));

        return $this->create($entityManager, $request, $transaction);
    }

    /**
     * Création d'une transaction.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request                $request
     * @param Transaction            $transaction
     *
     * @return Response
     */
    private function create(EntityManagerInterface $entityManager, Request $request, Transaction $transaction): Response
    {
        $workflow = new TransactionWorkFlow($entityManager, $transaction);
        $transaction = $workflow->getTransaction();
        $form = $this->createForm($workflow->getForm(), $transaction, ['transaction_type' => $workflow->getType()]);
        if ($workflow->isTransfer()) {
            $form->get('source')->setData($transaction->getAccount());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $workflow->checkForm($form)) {
            $workflow->add($form);
            $this->addFlash('success', sprintf('La création %s a bien été prise en compte', $transaction->getType()->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Mets à jour une transaction.
     *
     * @Route("/account/transactions/edit/{id}", name="transaction__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if (null !== $this->checkUpdate($transaction)) {
            return $this->checkUpdate($transaction);
        }

        $workflow = new TransactionWorkFlow($entityManager, $transaction);
        $transaction = $workflow->getTransaction();
        $form = $this->createForm($workflow->getForm(), $transaction, ['transaction_type' => $workflow->getType()]);
        if ($workflow->isTransfer()) {
            $form->get('source')->setData($transaction->getTransfer()->getAccount()); // Compte débiteur
            $form->get('target')->setData($transaction->getAccount()); // Compte créditeur
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $workflow->checkForm($form)) {
            $workflow->update($form);
            $this->addFlash('success', sprintf('La modification %s a bien été prise en compte', $transaction->getType()->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Supprime une transaction.
     *
     * @Route("/account/transactions/remove/{id}", name="transaction__remove")
     */
    public function remove(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if (null !== $this->checkUpdate($transaction)) {
            return $this->checkUpdate($transaction);
        }

        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workflow = new TransactionWorkFlow($entityManager, $transaction);
            $workflow->remove();
            $this->addFlash('success', sprintf('La suppression %s a bien été prise en compte', $transaction->getType()->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-delete.html.twig', [
            'form' => $form,
            'element' => 'cette opération',
        ]);
    }

    /**
     * Vérifie si on peut supprimer ou modifier la transaction.
     *
     * @param Transaction $transaction
     *
     * @return Response|null
     */
    private function checkUpdate(Transaction $transaction): ?Response
    {
        if (null !== $transaction->getTransfer()) {
            if (Transaction::STATE_RECONCILIED === $transaction->getTransfer()->getState()) {
                return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                    'message' => 'Impossible de supprimer ce virement !',
                ]);
            }
        }
        if (Transaction::STATE_RECONCILIED === $transaction->getState()) {
            return $this->renderForm('@OlixBackOffice/Include/modal-content-error.html.twig', [
                'message' => 'Impossible de supprimer cette transaction !',
            ]);
        }

        return null;
    }
}
