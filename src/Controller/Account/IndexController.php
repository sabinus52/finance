<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Entity\Account;
use App\Helper\Charts\MonthChart;
use App\Helper\Charts\SlipperyChart;
use App\Helper\Charts\YearChart;
use App\Helper\Performance;
use App\Repository\TransactionRepository;
use App\WorkFlow\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends BaseController
{
    #[Route(path: '/compte-courant/{id}', name: 'account_1_index')]
    public function indexDeposit(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/1deposit.html.twig');
    }

    #[Route(path: '/compte-epargne/{id}', name: 'account_2_index')]
    public function indexThrift(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/2thrift.html.twig');
    }

    #[Route(path: '/compte-a-terme/{id}', name: 'account_3_index')]
    public function indexTerm(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/3term.html.twig');
    }

    #[Route(path: '/portefeuille-boursier/{id}', name: 'account_4_index')]
    public function indexWallet(Request $request, Account $account, EntityManagerInterface $manager, TransactionRepository $repository): Response
    {
        $wallet = new Wallet($manager, $account);
        $result = $wallet->getTransactionHistories();

        $performance = new Performance($repository, $account);
        $performance->setTransactions($result);

        $chart2 = new MonthChart();
        $chart3 = new SlipperyChart();
        $chart4 = new YearChart();

        return $this->index($request, $account, 'account/4wallet.html.twig', [
            'wallet' => $wallet,
            'results' => $result,
            'operations' => $wallet->getTransactions(),
            'itemsbyMonth' => array_slice($performance->getByMonth(), -12, 12, true),
            'itemsbyQuarter' => array_slice($performance->getByQuarter(), -12, 12, true),
            'itemsbyYear' => $performance->getByYear(),
            'charts' => [
                'slippery' => $chart3->getChart($performance->getBySlippery()),
                'year' => $chart4->getChart($performance->getByYear()),
                'month' => $chart2->getChart($performance->getByMonth()),
            ],
        ]);
    }

    #[Route(path: '/contrat-de-capitalisation/{id}', name: 'account_5_index')]
    public function indexCapital(Request $request, Account $account, TransactionRepository $repository): Response
    {
        $performance = new Performance($repository, $account);

        $chart2 = new MonthChart();
        $chart3 = new SlipperyChart();
        $chart4 = new YearChart();

        return $this->index($request, $account, 'account/5capital.html.twig', [
            'itemsbyMonth' => array_slice($performance->getByMonth(), -12, 12, true),
            'itemsbyQuarter' => array_slice($performance->getByQuarter(), -12, 12, true),
            'itemsbyYear' => $performance->getByYear(),
            'itemsSlippery' => $performance->getBySlippery(),
            'charts' => [
                'slippery' => $chart3->getChart($performance->getBySlippery()),
                'year' => $chart4->getChart($performance->getByYear()),
                'month' => $chart2->getChart($performance->getByMonth()),
            ],
        ]);
    }

    /**
     * @param array<mixed> $parameters
     */
    private function index(Request $request, Account $account, string $template, array $parameters = []): Response
    {
        $formFilter = $this->createFormFilter();

        // Remplit le formulaire avec les données de la session
        $session = $request->getSession();
        $filter = $session->get('filter');
        if (null !== $filter) {
            foreach ($filter as $key => $value) {
                if ($formFilter->has($key)) {
                    $formFilter->get($key)->setData($value);
                }
            }
        }

        return $this->render($template, array_merge([
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'filter' => $formFilter->createView(),
            ],
        ], $parameters));
    }
}
