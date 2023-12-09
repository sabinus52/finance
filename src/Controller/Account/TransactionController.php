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
use App\Entity\Stock;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Transaction\TransactionModelInterface;
use App\Transaction\TransactionModelRouter;
use App\Values\StockPosition;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends BaseController
{
    /**
     * Création d'une transaction standard par type (recette ou dépense).
     *
     * @Route("/account/{id}/create/transaction/bytype/{type}", name="transaction_create_bytype", methods={"GET", "POST"})
     */
    public function createTransactionByType(Request $request, Account $account, string $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createStandardByType((bool) $type));
    }

    /**
     * Création d'une transaction standard par catégorie (interet, etc).
     *
     * @Route("/account/{id}/create/transaction/bycat/{codecat}", name="transaction_create_bycat", methods={"GET", "POST"})
     */
    public function createTransactionByCategory(Request $request, Account $account, string $codecat, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createStandardByCategory($codecat));
    }

    /**
     * Création d'un virement.
     *
     * @Route("/account/{id}/create/transfer/{type}", name="transfer_create", methods={"GET", "POST"})
     */
    public function createTransfer(Request $request, Account $account, string $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createTransferByCategory($type));
    }

    /**
     * Création d'une valorisation sur un placement.
     *
     * @Route("/account/{id}/create/capital", name="capital_create", methods={"GET", "POST"})
     */
    public function createValorisation(Request $request, Account $account, EntityManagerInterface $entityManager, TransactionRepository $repository): Response
    {
        // Recherche la dernière transaction de valorisation
        $last = $repository->findOneLastValorisation($account);
        $date = new DateTime();
        if (null !== $last) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createRevaluation($date));
    }

    /**
     * Création d'une transaction de frais de véhicule.
     *
     * @Route("/account/{id}/create/transaction/vehicle/{type}", name="transaction_create_vehicle", methods={"GET", "POST"})
     */
    public function createTransactionVehicle(Request $request, Account $account, string $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createVehicle($type));
    }

    /**
     * Création d'une transaction d'une opération boursière.
     *
     * @Route("/account/{id}/create/transaction/stock/{type}", name="transaction_create_wallet", methods={"GET", "POST"})
     */
    public function createTransactionStock(Request $request, Account $account, int $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->create($request, $account, $router->createStock(new StockPosition($type)));
    }

    /**
     * Création d'une transaction d'une opération boursière.
     *
     * @Route("/account/{id}/create/transaction/stock/{type}/{stock}", name="transaction_create_wallet_stock", methods={"GET", "POST"})
     */
    public function createTransactionStockWithStock(Request $request, Account $account, int $type, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        $model = $router->createStock(new StockPosition($type));
        $model->setDatas(['transactionStock' => ['stock' => $stock]]);

        return $this->create($request, $account, $model);
    }

    /**
     * Création d'une transaction.
     *
     * @param Request                   $request
     * @param TransactionModelInterface $modelTransaction
     *
     * @return Response
     */
    private function create(Request $request, Account $account, TransactionModelInterface $modelTransaction): Response
    {
        $modelTransaction->setAccount($account);
        $transaction = $modelTransaction->getTransaction();

        $form = $this->createForm($modelTransaction->getFormClass(), $transaction, $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($transaction->getAccount());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('La création %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Créer %s', $modelTransaction->getFormTitle()),
            ],
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

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Modifier %s', $modelTransaction->getFormTitle()),
            ],
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
            $router = new TransactionModelRouter($entityManager);
            $modelTransaction = $router->load($transaction);
            $modelTransaction->delete();
            $this->addFlash('success', sprintf('La suppression %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('cette opération de <b>%s</b>', $transaction),
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
