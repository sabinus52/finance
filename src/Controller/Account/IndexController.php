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

    private function index(Request $request, Account $account, string $template): Response
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

        return $this->render($template, [
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'filter' => $formFilter->createView(),
            ],
        ]);
    }
}
