<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Datatable\RecipientTableType;
use App\Entity\Recipient;
use App\Form\RecipientType;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des bénéficiares.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class RecipientController extends AbstractController
{
    #[Route(path: '/manage/recipient', name: 'manage_recipient__index')]
    public function index(Request $request, DataTableFactory $factory): Response
    {
        $datatable = $factory->createFromType(RecipientTableType::class)
            ->handleRequest($request)
        ;

        if ($datatable->isCallback()) {
            return $datatable->getResponse();
        }

        return $this->render('manage/recipient-index.html.twig', [
            'datatable' => $datatable,
        ]);
    }

    #[Route(path: '/manage/recipient/create', name: 'manage_recipient__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipient = new Recipient();
        $form = $this->createForm(RecipientType::class, $recipient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recipient);
            $entityManager->flush();

            $this->addFlash('success', 'La création du bénéficiare <strong>'.$recipient.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau bénéficiaire',
            ],
        ]);
    }

    #[Route(path: '/manage/recipient/edit/{id}', name: 'manage_recipient__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Recipient $recipient, EntityManagerInterface $entityManager): Response
    {
        // Bénéficiaire réservé pour les virements internes
        if (1 === $recipient->getId()) {
            return $this->render('@OlixBackOffice/Include/modal-alert.html.twig', [
                'message' => 'Ce bénéficiaire <strong>'.$recipient.'</strong> ne peut pas être modifié.',
            ]);
        }

        $form = $this->createForm(RecipientType::class, $recipient);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du bénéficiare <strong>'.$recipient.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un bénéficiaire',
            ],
        ]);
    }
}
