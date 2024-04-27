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
use App\Entity\TransactionStock;
use App\Entity\TransactionVehicle;
use App\Transaction\TransactionModelRouter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller des transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TransactionController extends BaseController
{
    /**
     * Création d'une transaction standard par type (recette ou dépense).
     */
    #[Route(path: '/account/{id}/create/transaction/bytype/{type}', name: 'transaction_create_bytype', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createTransactionByType(Request $request, Account $account, string $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $account, $router->createStandardByType((bool) $type));
    }

    /**
     * Création d'une transaction standard par catégorie (interet, etc).
     */
    #[Route(path: '/account/{id}/create/transaction/bycat/{codecat}', name: 'transaction_create_bycat', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createTransactionByCategory(Request $request, Account $account, string $codecat, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $account, $router->createStandardByCategory($codecat));
    }

    /**
     * Création d'un virement.
     */
    #[Route(path: '/account/{id}/create/transfer/{type}', name: 'transfer_create', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createTransfer(Request $request, Account $account, string $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $account, $router->createTransferByCategory($type));
    }

    /**
     * Mets à jour une transaction.
     */
    #[Route(path: '/account/transactions/edit/{id}', name: 'transaction__edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if ($this->checkUpdate($transaction) instanceof Response) {
            return $this->checkUpdate($transaction);
        }

        $router = new TransactionModelRouter($entityManager);
        $modelTransaction = $router->load($transaction);
        $transaction = $modelTransaction->getTransaction();

        $form = $this->createForm($modelTransaction->getFormClass(), $transaction, $modelTransaction->getFormOptions());
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($transaction->getTransfer()->getAccount()); // Compte débiteur
            $form->get('amount')->setData(abs($transaction->getTransfer()->getAmount()));
            $form->get('target')->setData($transaction->getAccount()); // Compte créditeur
            if ($form->has('invest')) {
                $form->get('invest')->setData($transaction->getAmount());
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->update($form);
            $this->addFlash('success', sprintf('La modification %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Modifier %s', $modelTransaction->getFormTitle()),
            ],
        ]);
    }

    /**
     * Clone une transaction.
     */
    #[Route(path: '/account/transactions/clone/{id}', name: 'transaction__clone', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function clone(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);
        $modelTransaction = $router->load($transaction);
        $transaction = clone $modelTransaction->getTransaction();
        $transaction->setDate(new \DateTimeImmutable());
        $transaction->setState(Transaction::STATE_NONE);
        // Cas d'une transaction de véhicule
        if ($transaction->getTransactionVehicle() instanceof TransactionVehicle) {
            $vehicle = clone $transaction->getTransactionVehicle();
            $transaction->setTransactionVehicle($vehicle);
        }
        // Cas d'une transaction d'un orfre boursier
        if ($transaction->getTransactionStock() instanceof TransactionStock) {
            $stock = clone $transaction->getTransactionStock();
            $transaction->setTransactionStock($stock);
        }
        $modelTransaction->setTransaction($transaction);

        $form = $this->createForm($modelTransaction->getFormClass(), $transaction, $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($transaction->getTransfer()->getAccount()); // Compte débiteur
            $form->get('amount')->setData(abs($transaction->getTransfer()->getAmount()));
            $form->get('target')->setData($transaction->getAccount()); // Compte créditeur
            $transaction->setTransfer(null);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('Le clonage %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Cloner %s', $modelTransaction->getFormTitle()),
                'btnlabel' => 'Cloner',
            ],
        ]);
    }

    /**
     * Supprime une transaction.
     */
    #[Route(path: '/account/transactions/remove/{id}', name: 'transaction__remove', requirements: ['id' => '\d+'])]
    public function remove(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if ($this->checkUpdate($transaction) instanceof Response) {
            return $this->checkUpdate($transaction);
        }

        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $router = new TransactionModelRouter($entityManager);
            $modelTransaction = $router->load($transaction);
            $modelTransaction->delete();
            $this->addFlash('success', sprintf('La suppression %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('cette opération de <b>%s</b>', $transaction),
        ]);
    }

    /**
     * Vérifie si on peut supprimer ou modifier la transaction.
     */
    private function checkUpdate(Transaction $transaction): ?Response
    {
        if ($transaction->getTransfer() instanceof Transaction && Transaction::STATE_RECONCILIED === $transaction->getTransfer()->getState()) {
            return $this->render('@OlixBackOffice/Include/modal-content-error.html.twig', [
                'message' => 'Impossible de supprimer ce virement !',
            ]);
        }
        if (Transaction::STATE_RECONCILIED === $transaction->getState()) {
            return $this->render('@OlixBackOffice/Include/modal-content-error.html.twig', [
                'message' => 'Impossible de supprimer cette transaction !',
            ]);
        }

        return null;
    }
}
