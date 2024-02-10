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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controleur des comptes et contrats.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ReconciliationController extends BaseController
{
    /**
     * Validation du formulaire de saisie du solde à rapprocher.
     */
    #[Route(path: '/account/{id}/reconciliation/create', name: 'reconciliation_create')]
    public function create(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormReconBalance($account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $metaBalance = clone $account->getBalance();
            $metaBalance->setReconCurrent($form->get('balance')->getData());
            $account->setBalance($metaBalance);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le compte <strong>%s</strong> est prêt pour le rapprochement bancaire.', $account->getFullName()));

            // Retourne l'url pour rediriger vers la page en javascript
            return new Response($this->generateUrl('reconciliation_index', ['id' => $account->getId()]));
        }

        return $this->renderForm('account/reconciliation-create.html.twig', [
            'form' => $form,
            'account' => $account,
        ]);
    }

    /**
     * Page principale de rapprochement.
     */
    #[Route(path: '/account/{id}/reconciliation', name: 'reconciliation_index')]
    public function index(Account $account, TransactionRepository $repository): Response
    {
        $formDelete = $this->createFormBuilder()->getForm();
        $formReconValid = $this->createFormBuilder()->getForm();

        $transactions = $repository->findToReconciliation($account);

        return $this->render('account/reconciliation.html.twig', [
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'delete' => $formDelete->createView(),
                'valid' => $formReconValid->createView(),
            ],
            'transactions' => $transactions,
            'gab' => $this->calculateGab($account, $transactions),
            'isReconcilied' => true,
        ]);
    }

    /**
     * Valide le rapprochement.
     */
    #[Route(path: '/account/{id}/reconciliation/valid', name: 'reconciliation_valid')]
    public function valid(Request $request, Account $account, TransactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $formReconValid = $this->createFormBuilder()->getForm();

        $formReconValid->handleRequest($request);
        if ($formReconValid->isSubmitted() && $formReconValid->isValid()) {
            $metaBalance = clone $account->getBalance();
            // Ecart en cours pour le calcul final
            $gab = $metaBalance->getReconBalance() - $metaBalance->getReconCurrent();

            // Valide le rapprochement final
            $transactions = $repository->findBy([
                'account' => $account,
                'state' => Transaction::STATE_RECONTEMP,
            ]);
            foreach ($transactions as $transaction) {
                $gab = round($gab + $transaction->getAmount(), 2);
                $transaction->setState(Transaction::STATE_RECONCILIED);
            }
            $metaBalance->setReconBalance($metaBalance->getReconCurrent());
            $account->setBalance($metaBalance);

            if (0.0 !== $gab) {
                $this->addFlash('error', 'Une erreur est survenue lors du rapprochement bancaire');

                return $this->redirectToRoute('reconciliation_index', ['id' => $account->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', sprintf('Le rapprochement bancaire de <strong>%s</strong> opérations a été effectué avec succès sur le compte <strong>%s</strong>', count($transactions), $account->getFullName()));

            return $this->redirectToRoute(sprintf('account_%s_index', $account->getType()->getTypeCode()), ['id' => $account->getId()]);
        }

        return $this->redirectToRoute('reconciliation_index', ['id' => $account->getId()]);
    }

    /**
     * Rapproche temporairement une transaction.
     */
    #[Route(path: '/account/reconciliation/{id}', name: 'reconciliation_check')]
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
     * Retourne le formulaire du solde en cours du rapprochement bancaire.
     *
     * @return FormInterface
     */
    protected function createFormReconBalance(Account $account): FormInterface
    {
        $form = $this->createFormBuilder()
            ->add('balance', MoneyType::class, [
                'label' => 'Solde à rapprocher',
                'required' => false,
                'constraints' => new NotBlank(),
            ])
            ->getForm()
        ;
        $form->get('balance')->setData($account->getBalance()->getReconCurrent());

        return $form;
    }

    /**
     * Calcule l'écart entre le solde du rapprochement courant et le dernier.
     *
     * @param Transaction[] $transactions
     *
     * @return float
     */
    private function calculateGab(Account $account, array $transactions): float
    {
        $gab = $account->getBalance()->getReconBalance() - $account->getBalance()->getReconCurrent();
        foreach ($transactions as $transaction) {
            if (Transaction::STATE_RECONTEMP === $transaction->getState()) {
                $gab = round($gab + $transaction->getAmount(), 2);
            }
        }

        return $gab;
    }
}
