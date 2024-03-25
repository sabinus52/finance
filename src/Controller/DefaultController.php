<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\ModelRepository;
use App\Values\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Olix\BackOfficeBundle\Helper\DoctrineHelper;
use Olix\BackOfficeBundle\Helper\SystemHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function index(AccountRepository $repository, ModelRepository $repotModel): Response
    {
        /** @var Account[] $accounts */
        $accounts = $repository->findBy([], ['institution' => 'ASC', 'name' => 'ASC']);

        $result = [];
        foreach ($accounts as $account) {
            $result[$account->getUnit()][$account->getType()->getTypeCode()][] = $account;
        }

        return $this->render('default/index.html.twig', [
            'accounts' => $result,
            'units' => $this->getParameter('app.account.units'),
            'types' => AccountType::$valuesGroupBy,
            'schedules' => $repotModel->findScheduleToDo(),
        ]);
    }

    #[Route(path: '/dump-base', name: 'dump_base')]
    public function dumpBase(Request $request, EntityManagerInterface $manager): Response
    {
        $pathRoot = (string) $this->getParameter('olix.backup.path'); /** @phpstan-ignore-line */

        // Sauvegarde
        $helper = new DoctrineHelper($manager);
        $return = $helper->dumpBase($pathRoot);

        // Purge
        $helper = new SystemHelper();
        $helper->purgeFiles($pathRoot, 'dump-*.sql', 5);

        if (0 === $return[0]) {
            $this->addFlash('success', sprintf('Le dump <strong>%s</strong> de la base a été sauvegardé avec succès', $return[1]));
        } else {
            $this->addFlash('error', sprintf('Echec du dump <strong>%s</strong> de la base', $return[1]));
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
