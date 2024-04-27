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
use App\Charts\PerformanceMonthChart;
use App\Charts\PerformanceSlipperyChart;
use App\Entity\Account;
use App\Entity\Stock;
use App\Helper\Performance;
use App\Transaction\TransactionModelRouter;
use App\Values\StockPosition;
use App\WorkFlow\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Controlleur des comptes boursiers.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @@SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class WalletAccountController extends BaseController
{
    /**
     * Page d'un compte boursier.
     */
    #[Route(path: '/portefeuille-boursier/{id}', name: 'account_4_index', requirements: ['id' => '\d+'])]
    public function indexWallet(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $wallet = new Wallet($entityManager, $account);
        $result = $wallet->getTransactionHistories();

        $performance = new Performance($entityManager, $account);
        $performance->setTransactions($result);

        return $this->indexAccount($request, $account, 'account/4wallet.html.twig', [
            'wallet' => $wallet,
            'results' => $result,
            'operations' => $wallet->getTransactions(),
            'itemsbyMonth' => array_slice($performance->getByMonth(), -12, 12, true),
            'itemsbyQuarter' => array_slice($performance->getByQuarter(), -12, 12, true),
            'itemsbyYear' => $performance->getByYear(),
            'charts' => [
                'slippery' => $this->getChartBySlippery($performance),
                'year' => $this->getChartByYear($performance),
                'month' => $this->getChartByMonth($performance),
            ],
        ]);
    }

    /**
     * Création d'une transaction d'une opération boursière SANS sélection de l'action.
     */
    #[Route(path: '/account/{id}/create/transaction/stock/{type}', name: 'transaction_create_wallet', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createTransactionStock(Request $request, Account $account, int $type, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $account, $router->createStock(new StockPosition($type)));
    }

    /**
     * Création d'une transaction d'une opération boursière AVEC sélection de l'action.
     */
    #[Route(path: '/account/{id}/create/transaction/stock/{type}/{stock}', name: 'transaction_create_wallet_stock', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createTransactionStockWithStock(Request $request, Account $account, int $type, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        $model = $router->createStock(new StockPosition($type));
        $model->setDatas(['transactionStock' => ['stock' => $stock]]);

        return $this->createTransaction($request, $account, $model);
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
}
