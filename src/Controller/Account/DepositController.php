<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Charts\BalanceDayChart;
use App\Charts\CategoryChart;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Helper\DateRange;
use App\Helper\Report\CategoryReport;
use App\Repository\ModelRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Controleur de compte de dépôt.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class DepositController extends BaseController
{
    /**
     * Page d'un compte de dépôt.
     */
    #[Route(path: '/compte-courant/{id}', name: 'account_1_index')]
    public function indexDeposit(Request $request, Account $account, TransactionRepository $transactionRepository, ModelRepository $modelRepository): Response
    {
        $this->account = $account;

        return $this->index($request, $account, 'account/1deposit.html.twig', [
            'categories' => $this->getCategoriesChart($transactionRepository),
            'balances' => $this->getPredictBalanceChart($transactionRepository, $modelRepository),
        ]);
    }

    /**
     * Retourne le graphique contenant les catégories les plus utilisés sur les 30 derniers jours.
     */
    private function getCategoriesChart(TransactionRepository $transactionRepository): Chart
    {
        $range = new DateRange(DateRange::LAST_30D);
        $chart = new CategoryChart();
        $report = new CategoryReport();
        $transactions = $transactionRepository->findExpenses($this->account, $range->getRange());
        $categories = $report->reGroupTotalAmountByCategory($transactions);

        return $chart->getChart($categories);
    }

    /**
     * Retourne le graphique des anciens soldes et des futures soldes.
     */
    private function getPredictBalanceChart(TransactionRepository $transactionRepository, ModelRepository $modelRepository): Chart
    {
        $now = new \DateTimeImmutable();
        $chart = new BalanceDayChart();

        // Transactions des derniers jours
        $transactions = $this->findLastTransactions90D($transactionRepository);

        // On récupère les transactions planifiées pour calculer la prédiction sur les  2 prochains mois
        $models = $modelRepository->findScheduleEnabled();
        foreach ($models as $model) {
            // Si le compte ou bien le compte de virement est concerné
            if ($model->getAccount() !== $this->account && $model->getTransfer() !== $this->account) {
                continue;
            }
            // Tant que la date de la prochaine planification est dans les 60 jours
            while ($model->getSchedule()->getDoAt() <= $now->modify('+ 60 days')) {
                $amount = $model->getAmount();
                // Si le compte est receveur du virement, on recoit le montant inverse
                if ($model->getTransfer() instanceof Account && $model->getTransfer() === $this->account) {
                    $amount *= -1;
                }

                // Ajoute la nouvelle future transaction
                $transaction = new Transaction();
                $transaction
                    ->setDate($model->getSchedule()->getDoAt())
                    ->setAmount($amount)
                ;
                $transactions[] = $transaction;
                $model->getSchedule()->setNextDoAt();
            }
        }

        return $chart->getChart($transactions);
    }

    /**
     * Retourne toutes les transactions (mode light) des 90 derniers jours.
     *
     * @return Transaction[]
     */
    private function findLastTransactions90D(TransactionRepository $transactionRepository): array
    {
        $range = new DateRange(DateRange::LAST_90D);

        return $transactionRepository->createQueryBuilder('trt')
            ->andWhere('trt.account = :account')
            ->andWhere('trt.date >= :start')
            ->setParameter('account', $this->account)
            ->setParameter('start', $range->getDateStart())
            ->orderBy('trt.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
