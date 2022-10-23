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
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Form\TransferType;
use App\Helper\DateRange;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des comptes et contrats.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionController extends AbstractController
{
    /**
     * Page d'accueil des comptes.
     *
     * @Route("/account/{id}/transactions", name="account__index")
     */
    public function index(Request $request, TransactionRepository $repository, Account $account): Response
    {
        $formFilter = $this->getFormFilter();
        $formDelete = $this->createFormBuilder()->getForm();

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
        $session->set('filter', '');

        return $this->renderForm('account/transactions.html.twig', [
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'transactions' => $repository->findByAccount($account),
            'filter' => $formFilter,
            'delete' => $formDelete,
        ]);
    }

    /**
     * @Route("/account/{id}/transactions/ajax", name="account__ajax")
     */
    public function getListTransactionAjax(Request $request, TransactionRepository $repository, Account $account): Response
    {
        $datas = $request->get('form');

        $session = $request->getSession();
        $filter = $session->get('filter');
        if (null !== $filter) {
            $session->set('filter', $datas);
        }

        $filters = [];
        /*$range = $request->get('range');
        if ($range != null) {
            $filters['range'] = $range;
        }*/

        unset($datas['_token']);
        foreach ($datas as $key => $value) {
            // Si null alors pas de filtre
            if ('' === $value || null === $value) {
                continue;
            }
            if ('range' === $key) {
                $ttt = new DateRange($value);
                $value = $ttt->getRange();
            }
            $filters[$key] = $value;
        }
        /*if (null !== $type) {
            $filters['state'] = $type;
        }*/
        // var_dump($datas);

        return $this->render('account/transaction-table.html.twig', [
            'transactions' => $repository->findByAccount($account, $filters),
        ]);
    }

    /**
     * Retourne le formulaire du filtre.
     *
     * @return FormInterface
     */
    private function getFormFilter(): FormInterface
    {
        return $this->createFormBuilder()
            ->add('range', ChoiceType::class, [
                'label' => 'Plage :',
                'choices' => DateRange::getChoices(),
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Etat :',
                'choices' => [
                    'Tous les états' => null,
                    'Non rapproché' => 0,
                    'Rapproché' => 1,
                ],
                'choice_value' => fn ($value) => $value,
            ])
            ->getForm()
        ;
    }

    /**
     * @Route("/account/{id}/transactions/create", name="transaction__create", methods={"GET", "POST"})
     */
    public function createTransaction(Request $request, Account $account, TransactionRepository $repository): Response
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
                $repository->add($transaction, true);
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
    public function createTransfer(Request $request, Account $account, TransactionRepository $repository): Response
    {
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transaction->setPayment(new Payment(Payment::INTERNAL));
        $transaction->setRecipient(new Recipient());
        $transaction->setCategory(new Category());
        $form = $this->createForm(TransferType::class, $transaction, [
            'action' => $this->generateUrl('transfer__create', ['id' => $account->getId()]),
        ]);
        $form->get('source')->setData($account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->addTransfer($transaction, $form->get('source')->getData(), $form->get('target')->getData(), true);
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
    public function update(Request $request, Transaction $transaction, TransactionRepository $repository): Response
    {
        if (null !== $transaction->getTransfer()) {
            return $this->updateTransfer($request, $transaction, $repository);
        }

        return $this->updateTransaction($request, $transaction, $repository);
    }

    private function updateTransaction(Request $request, Transaction $transaction, TransactionRepository $repository): Response
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
                $repository->update(true);
                $this->addFlash('success', 'La modification de l\'opération a bien été prise en compte');

                return new Response('OK');
            }
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    private function updateTransfer(Request $request, Transaction $transaction, TransactionRepository $repository): Response
    {
        // Si le montant est négatif, on doit prendre en source le compte créditeur
        if ($transaction->getAmount() < 0) {
            $transaction = $transaction->getTransfer();
        }

        $form = $this->createForm(TransferType::class, $transaction, [
            'action' => $this->generateUrl('transaction__edit', ['id' => $transaction->getId()]),
        ]);
        $form->get('source')->setData($transaction->getTransfer()->getAccount()); // Compte débiteur
        $form->get('target')->setData($transaction->getAccount()); // Compte créditeur

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->updateTransfer($transaction, $form->get('source')->getData(), $form->get('target')->getData(), true);

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
    public function remove(Request $request, Transaction $transaction, TransactionRepository $repository): Response
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->remove($transaction);

            $this->addFlash('success', 'La suppression a bien été prise en compte');

            return new Response('OK');
        }

        $this->addFlash('danger', 'Erreur lors de la suppression');

        return new Response('OK');
    }
}
