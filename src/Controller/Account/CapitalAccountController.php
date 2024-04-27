<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Charts\PerformanceByYearChart;
use App\Charts\PerformanceCapitalChart;
use App\Charts\PerformanceMonthChart;
use App\Charts\PerformanceSlipperyChart;
use App\Entity\Account;
use App\Entity\Stock;
use App\Entity\Transaction;
use App\Helper\Performance;
use App\Repository\TransactionRepository;
use App\Transaction\TransactionModelRouter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Controleur des comptes de capitalisation.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class CapitalAccountController extends BaseController
{
    /**
     * Page d'un compte de capitalisation.
     */
    #[Route(path: '/contrat-de-capitalisation/{id}', name: 'account_5_index', requirements: ['id' => '\d+'])]
    public function indexCapital(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $performance = new Performance($entityManager, $account);

        return $this->indexAccount($request, $account, 'account/5capital.html.twig', [
            'itemsbyMonth' => array_slice($performance->getByMonth(), -12, 12, true),
            'itemsbyQuarter' => array_slice($performance->getByQuarter(), -12, 12, true),
            'itemsbyYear' => $performance->getByYear(),
            'itemsSlippery' => $performance->getBySlippery(),
            'charts' => [
                'slippery' => $this->getChartBySlippery($performance),
                'year' => $this->getChartByYear($performance),
                'month' => $this->getChartByMonth($performance),
                'capital' => $this->getChartCapital($performance, $entityManager),
            ],
        ]);
    }

    /**
     * Création d'une valorisation sur un placement.
     */
    #[Route(path: '/account/{id}/create/capital', name: 'capital_create', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createValorisation(Request $request, Account $account, EntityManagerInterface $entityManager, TransactionRepository $repository): Response
    {
        // Recherche la dernière transaction de valorisation
        $last = $repository->findOneLastValorisation($account);
        $date = new \DateTimeImmutable();
        if ($last instanceof Transaction) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $account, $router->createRevaluation($date));
    }

    /**
     * Retourne le graphique de la performance glissante.
     */
    private function getChartBySlippery(Performance $performance): Chart
    {
        $chart = new PerformanceSlipperyChart();

        return $chart->getChart($performance->getBySlippery());
    }

    /**
     * Retourne le graphique de la performance annuelle.
     */
    private function getChartByYear(Performance $performance): Chart
    {
        $chart = new PerformanceByYearChart();

        return $chart->getChart($performance->getByYear());
    }

    /**
     * Retourne le graphique de la performance mensuelle.
     */
    private function getChartByMonth(Performance $performance): Chart
    {
        $chart = new PerformanceMonthChart();

        return $chart->getChart($performance->getByMonth());
    }

    /**
     * Retourne le graphique de la performance du capital.
     */
    private function getChartCapital(Performance $performance, EntityManagerInterface $entityManager): Chart
    {
        $chart = new PerformanceCapitalChart();
        // Taux d'intérêt
        $indices = $entityManager->getRepository(Stock::class)->findOneBy(['type' => Stock::INTEREST_RATE]);

        return $chart->getChart($performance->getByMonth($indices));
    }
}
