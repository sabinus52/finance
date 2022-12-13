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
use App\Helper\Performance;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends BaseController
{
    /**
     * @Route("/compte-courant/{id}", name="account_1_index")
     */
    public function indexDeposit(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/1deposit.html.twig');
    }

    /**
     * @Route("/compte-epargne/{id}", name="account_2_index")
     */
    public function indexThrift(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/2thrift.html.twig');
    }

    /**
     * @Route("/compte-a-terme/{id}", name="account_3_index")
     */
    public function indexTerm(Request $request, Account $account): Response
    {
        return $this->index($request, $account, 'account/3term.html.twig');
    }

    /**
     * @Route("/contrat-de-capitalisation/{id}", name="account_5_index")
     */
    public function indexCapital(Request $request, Account $account, TransactionRepository $repository): Response
    {
        $performance = new Performance($repository, $account);

        return $this->index($request, $account, 'account/5capital.html.twig', [
            'itemsbyMonth' => array_slice($performance->getByMonth(), -12, 12, true),
            'itemsbyQuarter' => array_slice($performance->getByQuarter(), -12, 12, true),
            'itemsbyYear' => $performance->getByYear(),
        ]);
    }

    /**
     * Undocumented function.
     *
     * @param Request      $request
     * @param Account      $account
     * @param string       $template
     * @param array<mixed> $parameters
     *
     * @return Response
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
