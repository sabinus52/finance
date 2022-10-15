<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Datatable\RecipientDatatable;
use App\Entity\Recipient;
use App\Form\RecipientType;
use Doctrine\ORM\EntityManagerInterface;
use Olix\BackOfficeBundle\Datatable\Response\DatatableResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des bénéficiares.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ManageRecipientController extends AbstractController
{
    /**
     * @Route("/manage/recipient", name="manage_recipient__index")
     */
    public function index(Request $request, RecipientDatatable $datatable, DatatableResponse $responseService): Response
    {
        $isAjax = $request->isXmlHttpRequest();

        $datatable->buildDatatable();

        if ($isAjax) {
            $responseService->setDatatable($datatable);
            $responseService->getDatatableQueryBuilder();

            return $responseService->getResponse();
        }

        return $this->renderForm('manage/recipient-index.html.twig', [
            'datatable' => $datatable,
        ]);
    }

    /**
     * @Route("/manage/recipient/create", name="manage_recipient__create", methods={"GET", "POST"})
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipient = new Recipient();
        $form = $this->createForm(RecipientType::class, $recipient, [
            'action' => $this->generateUrl('manage_recipient__create'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recipient);
            $entityManager->flush();

            $this->addFlash('success', 'La création du bénéficiare <strong>'.$recipient->getName().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('manage/recipient-create.html.twig', [
            'action' => 'create',
            'form' => $form,
        ]);
    }

    /**
     * @Route("/manage/recipient/edit/{id}", name="manage_recipient__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Recipient $recipient, EntityManagerInterface $entityManager): Response
    {
        // Bénéficiaire réservé pour les virements internes
        if (1 === $recipient->getId()) {
            return $this->renderForm('@OlixBackOffice/Include/modal-alert.html.twig', [
                'message' => 'Ce bénéficiaire <strong>'.$recipient->getName().'</strong> ne peut pas être modifié.',
            ]);
        }

        $form = $this->createForm(RecipientType::class, $recipient, [
            'action' => $this->generateUrl('manage_recipient__edit', ['id' => $recipient->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du bénéficiare <strong>'.$recipient->getName().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('manage/recipient-update.html.twig', [
            'action' => 'update',
            'form' => $form,
        ]);
    }
}
