<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Entity\Project;
use App\Entity\Transaction;
use App\Form\ProjectFormType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur de la gestion des projets.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ProjectManagerController extends AbstractController
{
    /**
     * Création d'un nouveau projet.
     */
    #[Route(path: '/rapport/projet/creation', name: 'report_project__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('success', sprintf('La création du projet <strong>%s</strong> a bien été prise en compte', $project));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau projet',
            ],
        ]);
    }

    /**
     * Modification d'un projet.
     */
    #[Route(path: '/rapport/projet/modification/{id}', name: 'report_project__edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectFormType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('La modification du projet <strong>%s</strong> a bien été prise en compte', $project));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un projet',
            ],
        ]);
    }

    /**
     * Rajoute des nouvelles transaction à effecter au projet.
     */
    #[Route(path: '/rapport/projet/transaction/{id}/add', name: 'report_project__addtrt', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function addTransaction(Request $request, Project $project, TransactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        // Liste des transaction selectionnées
        $transactions = $request->get('transaction');

        foreach ($transactions as $id) {
            $transaction = $repository->find($id);
            $transaction->setProject($project);
        }
        $entityManager->flush();
        $this->addFlash('success', sprintf("L'ajout de <strong>%s</strong> transaction(s) au projet <strong>%s</strong> a bien été prise en compte", count($transactions), $project));

        return new Response('OK');
    }

    /**
     * Supprime des transactions du projet en cours.
     */
    #[Route(path: '/rapport/projet/transaction/{id}/remove/{transaction}', name: 'report_project__deltrt', requirements: ['id' => '\d+', 'transaction' => '\d+'], methods: ['GET', 'POST'])]
    public function removeTransaction(Request $request, Project $project, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction->setProject(null);
            $entityManager->flush();
            $this->addFlash('success', sprintf('La suppression de <strong>%s</strong> du projet <strong>%s</strong> a bien été prise en compte', $transaction, $project));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('de l\'opération <strong>%s</strong> du projet <strong>%s</strong>', $transaction, $project),
        ]);
    }
}
