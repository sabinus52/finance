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
use App\Form\AccountType;
use App\Repository\AccountRepository;
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
class ManageAccountController extends AbstractController
{
    /**
     * @Route("/manage/account", name="manage_account__index")
     */
    public function index(AccountRepository $repository): Response
    {
        return $this->render('manage/account-index.html.twig', [
            'accounts' => $repository->findBy([], ['institution' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    /**
     * @Route("/manage/account/create", name="manage_account__create", methods={"GET", "POST"})
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $account = new Account();
        $form = $this->createForm(AccountType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($account);
            $entityManager->flush();

            $this->addFlash('success', 'La création du compte <strong>'.$account->getName().'</strong> a bien été prise en compte');

            return $this->redirectToRoute('manage_account__index');
        }

        return $this->renderForm('manage/account-edit.html.twig', [
            'action' => 'create',
            'form' => $form,
        ]);
    }

    /**
     * @Route("/manage/account/edit/{id}", name="manage_account__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AccountType::class, $account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du compte <strong>'.$account->getName().'</strong> a bien été prise en compte');

            return $this->redirectToRoute('manage_account__index');
        }

        return $this->renderForm('manage/account-edit.html.twig', [
            'action' => 'update',
            'form' => $form,
        ]);
    }
}
