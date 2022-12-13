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
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Form\TransferType;
use App\Form\ValorisationType;
use App\Helper\Balance;
use App\Helper\Transfer;
use App\Repository\CategoryRepository;
use App\Repository\RecipientRepository;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
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
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $form = $this->createForm(TransactionType::class, $transaction);

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
     * Création d'un virement.
     *
     * @Route("/account/{id}/transfer/create", name="transfer__create", methods={"GET", "POST"})
     */
    public function createVirement(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        return $this->createTransfer(Category::VIREMENT, $request, $account, $entityManager);
    }

    /**
     * Création d'un investissement.
     *
     * @Route("/account/{id}/invest/create", name="invest__create", methods={"GET", "POST"})
     */
    public function createInvestment(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        return $this->createTransfer(Category::INVESTMENT, $request, $account, $entityManager);
    }

    /**
     * Création d'une valorisation sur un placement.
     *
     * @Route("/account/{id}/capital/create", name="capital__create", methods={"GET", "POST"})
     */
    public function createValorisation(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        /** @var TransactionRepository $repository */
        $repository = $entityManager->getRepository(Transaction::class);
        /** @var RecipientRepository $repoRpt */
        $repoRpt = $entityManager->getRepository(Recipient::class);
        /** @var CategoryRepository $repoCat */
        $repoCat = $entityManager->getRepository(Category::class);

        // Derniere transaction pour récuperer la date de la dernière valorisation
        /** @var Transaction $last */
        $last = $repository->findOneLastValorisation($account);
        $date = new DateTime();
        if (null !== $last) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        // Préremplit le formulaire
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transaction->setDate($date->modify('last day of this month'));
        $transaction->setAmount(0);
        $transaction->setPayment(new Payment(Payment::INTERNAL));
        $transaction->setRecipient($repoRpt->find(1));
        $transaction->setCategory($repoCat->findByCode(sprintf('%s+', Category::REVALUATION)));

        $form = $this->createForm(ValorisationType::class, $transaction);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->getDate()->modify('last day of this month');
            if (null !== $last) {
                $variation = $transaction->getBalance() - $last->getBalance();
                $transaction->setAmount($variation);
                $transaction->setCategory($repoCat->findByCode(sprintf('%s%s', Category::REVALUATION, ($variation < 0) ? '-' : '+')));
            }
            $entityManager->persist($transaction);
            $entityManager->flush();
            $helper = new Balance($entityManager);
            $helper->updateBalanceAfter($transaction);

            $this->addFlash('success', 'La création de la transaction a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Création d'un transfert en fonction de son type.
     *
     * @param string                 $typeTransfer
     * @param Request                $request
     * @param Account                $account
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    private function createTransfer(string $typeTransfer, Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transfer = new Transfer($entityManager, $transaction);
        $transfer->setType($typeTransfer);

        $form = $this->createForm(TransferType::class, $transaction, ['transfer' => $typeTransfer]);
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
        if (null !== $this->checkUpdate($transaction)) {
            return $this->checkUpdate($transaction);
        }

        if (null !== $transaction->getTransfer()) {
            return $this->updateTransfer($request, $transaction, $entityManager);
        }

        if (Category::REVALUATION === $transaction->getCategory()->getCode()) {
            return $this->updateValorisation($request, $transaction, $entityManager);
        }

        return $this->updateTransaction($request, $transaction, $entityManager);
    }

    /**
     * Mise à jour d'une transaction.
     *
     * @param Request                $request
     * @param Transaction            $transaction
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    private function updateTransaction(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction);
        $dateBefore = $transaction->getDate();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->checkValid($transaction)) {
                $form->get('category')->addError(new FormError(''));
                $form->get('amount')->addError(new FormError(''));
            } else {
                $entityManager->flush();
                $helper = new Balance($entityManager);
                $helper->updateBalanceAfter($transaction, $dateBefore);
                $this->addFlash('success', 'La modification de l\'opération a bien été prise en compte');

                return new Response('OK');
            }
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Mise à jour d'un transfert.
     *
     * @param Request                $request
     * @param Transaction            $transaction
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    private function updateTransfer(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $transfer = new Transfer($entityManager, $transaction);
        $transaction = $transfer->getCredit();
        $dateBefore = $transaction->getDate();

        $form = $this->createForm(TransferType::class, $transaction, ['transfer' => $transfer->getType()]);
        $form->get('source')->setData($transfer->getDebit()->getAccount()); // Compte débiteur
        $form->get('target')->setData($transfer->getCredit()->getAccount()); // Compte créditeur

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->update();
            $helper = new Balance($entityManager);
            $helper->updateBalanceAfter($transfer->getDebit(), $dateBefore);
            $helper->updateBalanceAfter($transfer->getCredit(), $dateBefore);

            $this->addFlash('success', 'La modification du virement a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Mise à jour d'une transaction de valorisation de placement.
     *
     * @param Request                $request
     * @param Transaction            $transaction
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    private function updateValorisation(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        /** @var CategoryRepository $repoCat */
        $repoCat = $entityManager->getRepository(Category::class);
        $last = clone $transaction;

        $form = $this->createForm(ValorisationType::class, $transaction);
        $dateBefore = $transaction->getDate();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $variation = $transaction->getBalance() - $last->getBalance() + $last->getAmount();
            $transaction->setAmount($variation);
            $transaction->setCategory($repoCat->findByCode(sprintf('%s%s', Category::REVALUATION, ($variation < 0) ? '-' : '+')));
            $entityManager->flush();
            $helper = new Balance($entityManager);
            $helper->updateBalanceAfter($transaction, $dateBefore);
            $this->addFlash('success', 'La modification de l\'opération a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('account/transaction-update.html.twig', [
            'form' => $form,
        ]);
    }

    /**
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

        return $this->renderForm('@OlixBackOffice/Include/modal-delete.html.twig', [
            'form' => $form,
            'element' => 'cette opération',
        ]);
    }
}
