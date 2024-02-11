<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Entity\Account;
use App\Form\AccountType;
use App\Repository\AccountRepository;
use App\Values\AccountType as ValuesAccountType;
use App\WorkFlow\Balance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des comptes et contrats.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class AccountController extends AbstractController
{
    #[Route(path: '/manage/account', name: 'manage_account__index')]
    public function index(AccountRepository $repository): Response
    {
        /** @var Account[] $accounts */
        $accounts = $repository->findBy([], ['institution' => 'ASC', 'name' => 'ASC']);

        $result = [];
        // Initialise les données de types de comptes pour le regroupement
        foreach (ValuesAccountType::$valuesGroupBy as $key => $value) {
            $result[$key] = $value;
            $result[$key]['accounts'] = [];
        }

        foreach ($accounts as $account) {
            $result[$account->getType()->getTypeCode()]['accounts'][] = $account;
        }

        return $this->render('manage/account-index.html.twig', [
            'accounts' => $result,
        ]);
    }

    #[Route(path: '/manage/account/create', name: 'manage_account__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $account = new Account();
        $form = $this->createForm(AccountType::class, $account, [
            'choice_units' => $this->getParameter('app.account.units'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($account);
            $entityManager->flush();

            $this->addFlash('success', 'La création du compte <strong>'.$account.'</strong> a bien été prise en compte');

            return $this->redirectToRoute('manage_account__index');
        }

        return $this->render('manage/account-edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/manage/account/edit/{id}', name: 'manage_account__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AccountType::class, $account, [
            'choice_units' => $this->getParameter('app.account.units'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du compte <strong>'.$account.'</strong> a bien été prise en compte');

            return $this->redirectToRoute('manage_account__index');
        }

        return $this->render('manage/account-edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/manage/account/balance/{id}', name: 'manage_account__balance', methods: ['GET', 'POST'])]
    public function calculateBalance(Account $account, EntityManagerInterface $entityManager): Response
    {
        $helper = new Balance($entityManager);
        $result = $helper->updateBalanceFromScratch($account);

        $this->addFlash('success', sprintf('Le solde a été recalculé pour le compte <strong>%s</strong> sur <strong>%s</strong> opérations.', $account, $result));

        return $this->redirectToRoute('manage_account__index');
    }
}
