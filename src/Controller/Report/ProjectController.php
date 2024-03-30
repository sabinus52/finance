<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Charts\CategoryChart;
use App\Entity\Project;
use App\Entity\Transaction;
use App\Repository\ProjectRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur du rapport des projets.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ProjectController extends AbstractController
{
    /**
     * Page d'accueil du rapport de la liste de tous les véhicules.
     */
    #[Route(path: '/rapport/projets', name: 'report_project__index')]
    public function index(ProjectRepository $repository): Response
    {
        return $this->render('report/project.html.twig', [
            'projects' => $repository->findAllComplete(),
        ]);
    }

    /**
     * Page de rapport pour un projet.
     */
    #[Route(path: '/rapport/projet/{id}', name: 'report_project__item', requirements: ['id' => '\d+'])]
    public function reportByProject(Project $project, TransactionRepository $repository): Response
    {
        $chart = new CategoryChart();
        $transactions = $repository->findAllByProject($project);
        $categories = $this->reGroupTotalAmountByCategory($transactions);

        // Liste des transactions non sélectionnées durant la période du projet

        return $this->render('report/project-item.html.twig', [
            'project' => $project,
            'categories' => $categories,
            'transactions' => $transactions,
            'chart' => $chart->getChart($categories),
            'transactionsToComplete' => $this->getTransactionsToComplete($project, $repository),
        ]);
    }

    /**
     * Calcul de la somme total par regroupement des catégories.
     *
     * @param Transaction[] $transactions
     *
     * @return array<mixed>
     */
    private function reGroupTotalAmountByCategory(array $transactions): array
    {
        $categories = [];

        foreach ($transactions as $transaction) {
            $idCat = $transaction->getCategory()->getId();
            if (!array_key_exists($idCat, $categories)) {
                $categories[$idCat] = [
                    'datas' => $transaction->getCategory(),
                    'total' => 0.0,
                ];
            }
            $categories[$idCat]['total'] += $transaction->getAmount();
        }
        usort($categories, static fn ($aaa, $bbb): bool => $aaa['total'] > $bbb['total']);  /** @phpstan-ignore-line */

        return $categories;
    }

    /**
     * Retourne la liste des transactions non sélectionnées durant la période du projet
     * pour en rajouter une éventuellement.
     *
     * @return Transaction[]
     */
    private function getTransactionsToComplete(Project $project, TransactionRepository $repository): array
    {
        return $repository->createQueryBuilder('trt')
            ->addSelect('rpt')
            ->addSelect('cat')
            ->addSelect('prt')
            ->innerJoin('trt.recipient', 'rpt')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
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
    }
}
