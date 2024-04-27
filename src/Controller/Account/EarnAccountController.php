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
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controleur des comptes d'épargne.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class EarnAccountController extends BaseController
{
    /**
     * Page d'un compte épargne liquide.
     */
    #[Route(path: '/compte-epargne/{id}', name: 'account_2_index', requirements: ['id' => '\d+'])]
    public function indexThrift(Request $request, Account $account): Response
    {
        return $this->indexAccount($request, $account, 'account/2thrift.html.twig');
    }

    /**
     * Page d'un compte épargne à terme.
     */
    #[Route(path: '/compte-a-terme/{id}', name: 'account_3_index', requirements: ['id' => '\d+'])]
    public function indexTerm(Request $request, Account $account): Response
    {
        return $this->indexAccount($request, $account, 'account/3term.html.twig');
    }
}
