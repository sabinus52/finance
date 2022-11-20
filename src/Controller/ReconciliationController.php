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
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des comptes et contrats.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ReconciliationController extends TransactionBase
{
    /**
     * Page de rapprochement et de validation de celui-ci.
     *
     * @Route("/account/{id}/reconciliation", name="reconciliation_index")
     */
    public function index(Request $request, TransactionRepository $repository, Account $account, EntityManagerInterface $entityManager): Response
    {
        $formFilter = $this->createFormFilter(true);
        $formDelete = $this->createFormBuilder()->getForm();
        $formRecon = $this->createFormReconBalance($account);
        $formReconValid = $this->createFormBuilder()->getForm();

        $transactions = $repository->findToReconciliation($account);

        $formReconValid->handleRequest($request);
        if ($formReconValid->isSubmitted() && $formReconValid->isValid()) {
            // Valide le rapprochement final
            $result = $this->valid($account, $transactions, $entityManager);
            if (null !== $result) {
                $this->addFlash('success', sprintf('Le rapprochement bancaire de <strong>%s</strong> opérations a été effectué avec succès sur le compte <strong>%s</strong>', $result, $account->getFullName()));

                return $this->redirectToRoute('account__index', ['id' => $account->getId()]);
            }

            $this->addFlash('error', 'Une erreur est survenue lors du rapprochement bancaire');

            return $this->redirectToRoute('reconciliation_index', ['id' => $account->getId()]);
        }

        // Calcul de l'écart
        return $this->render('account/transactions.html.twig', [
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'filter' => $formFilter->createView(),
                'delete' => $formDelete->createView(),
                'recon' => $formRecon->createView(),
                'valid' => $formReconValid->createView(),
            ],
            'transactions' => $transactions,
            'gab' => $this->calculateGab($account, $transactions),
            'isReconcilied' => true,
        ]);
    }

    /**
     * Validation du formulaire de saisie du solde à rapprocher.
     *
     * @Route("/account/{id}/reconciliation/create", name="reconciliation_create")
     */
    public function create(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormReconBalance($account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $account->setReconCurrent($form->get('balance')->getData());
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le compte <strong>%s</strong> est prêt pour le rapprochement bancaire.', $account->getFullName()));

            // Retourne l'url pour rediriger vers la page en javascript
            return new Response($this->generateUrl('reconciliation_index', ['id' => $account->getId()]));
        }

        return $this->renderForm('account/transaction-reconciliation.html.twig', [
            'formRecon' => $form,
            'account' => $account,
        ]);
    }

    /**
     * Rapproche temporairement une transaction.
     *
     * @Route("/account/reconciliation/{id}", name="reconciliation_check", options={"expose": true})
     */
    public function reconcilie(Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if (Transaction::STATE_RECONTEMP === $transaction->getState()) {
            $transaction->setState(Transaction::STATE_NONE);
            $amount = $transaction->getAmount() * -1;
        } else {
            $transaction->setState(Transaction::STATE_RECONTEMP);
            $amount = $transaction->getAmount();
        }
        $entityManager->flush();

        return new JsonResponse([
            'code' => true,
            'amount' => $amount,
        ]);
    }

    /**
     * Valide le rapprochement.
     *
     * @param Account                $account
     * @param Transaction[]          $transactions
     * @param EntityManagerInterface $entityManager
     *
     * @return int|null
     */
    private function valid(Account $account, array $transactions, EntityManagerInterface $entityManager): ?int
    {
        $number = 0;
        $gab = $account->getReconBalance() - $account->getReconCurrent();

        foreach ($transactions as $transaction) {
            if (Transaction::STATE_RECONTEMP === $transaction->getState()) {
                $gab = round($gab + $transaction->getAmount(), 2);
                $transaction->setState(Transaction::STATE_RECONCILIED);
                ++$number;
            }
        }

        if (0.0 !== $gab) {
            return null;
        }

        $account->setReconBalance($account->getReconCurrent());
        $entityManager->flush();

        return $number;
    }

    /**
     * Calcule l'écart entre le solde du rapprochement courant et le dernier.
     *
     * @param Account       $account
     * @param Transaction[] $transactions
     *
     * @return float
     */
    private function calculateGab(Account $account, array $transactions): float
    {
        $gab = $account->getReconBalance() - $account->getReconCurrent();
        foreach ($transactions as $transaction) {
            if (Transaction::STATE_RECONTEMP === $transaction->getState()) {
                $gab = round($gab + $transaction->getAmount(), 2);
            }
        }

        return $gab;
    }
}
