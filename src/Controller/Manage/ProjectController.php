<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Entity\Project;
use App\Entity\Transaction;
use App\Form\ProjectFormType;
use App\Helper\Charts\CategoryChart;
use App\Repository\ProjectRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des projets.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ProjectController extends AbstractController
{
    /**
     * @Route("/manage/project", name="manage_project__index")
     */
    public function index(ProjectRepository $repository): Response
    {
        return $this->render('manage/project-index.html.twig', [
            'projects' => $repository->findAllComplete(),
        ]);
    }

    /**
     * @Route("/manage/project/infos/{id}", name="manage_project__item")
     */
    public function seeDatasItem(Project $project, TransactionRepository $repository): Response
    {
        $categories = [];
        $chart = new CategoryChart();

        // Calcul de la somme par regroupement des catégories
        foreach ($project->getTransactions() as $transaction) {
            $idCat = $transaction->getCategory()->getId();
            if (!array_key_exists($idCat, $categories)) {
                $categories[$idCat] = [
                    'datas' => $transaction->getCategory(),
                    'total' => 0.0,
                ];
            }
            $categories[$idCat]['total'] += $transaction->getAmount();
        }
        usort($categories, fn ($aaa, $bbb) => $aaa['total'] > $bbb['total']); /** @phpstan-ignore-line */

        // Liste des transactions non sélectionnées durant la période du projet
        $transactions = $repository->createQueryBuilder('trt')
            ->addSelect('rpt')
            ->addSelect('cat')
            ->innerJoin('trt.recipient', 'rpt')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.type = 0')
            ->andWhere('trt.date BETWEEN :start AND :end')
            ->andWhere('trt.id NOT IN (:ids)')
            ->setParameter('start', $project->getStartedAt()->format('y-m-d'))
            ->setParameter('end', $project->getFinishAt()->format('Y-m-d'))
            ->setParameter('ids', $project->getTransactions())
            ->orderBy('trt.date')
            ->getQuery()
            ->getResult()
        ;

        return $this->render('manage/project-item.html.twig', [
            'project' => $project,
            'categories' => $categories,
            'chart' => $chart->getChart($categories),
            'transactions' => $transactions,
        ]);
    }

    /**
     * @Route("/manage/project/create", name="manage_project__create", methods={"GET", "POST"})
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('success', 'La création du projet <strong>'.$project.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau projet',
            ],
        ]);
    }

    /**
     * @Route("/manage/project/edit/{id}", name="manage_project__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectFormType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du projet <strong>'.$project.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un projet',
            ],
        ]);
    }

    /**
     * @Route("/manage/project/transaction/{id}/add", name="manage_project__addtrt", methods={"GET", "POST"})
     */
    public function addTransaction(Request $request, Project $project, TransactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $transactions = $request->get('transaction');
        $result = print_r($transactions, true);

        foreach ($transactions as $id) {
            $transaction = $repository->find($id);
            $transaction->setProject($project);
        }
        $entityManager->flush();

        return new Response('rrrrez  rereerz'.$result);
    }

    /**
     * @Route("/manage/project/transaction/{id}/remove/{transaction}", name="manage_project__deltrt", methods={"GET", "POST"})
     */
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

        return $this->renderForm('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('de l\'opération <strong>%s</strong> du projet <strong>%s</strong>', $transaction, $project),
        ]);
    }
}
