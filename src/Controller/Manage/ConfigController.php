<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use Doctrine\ORM\EntityManagerInterface;
use Olix\BackOfficeBundle\Helper\DoctrineHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigController extends AbstractController
{
    #[Route(path: '/manage/config', name: 'manage_config__index')]
    public function index(): Response
    {
        // Récupération des fichiers de sauvegarde
        $pathBackup = (string) $this->getParameter('olix.backup.path'); /** @phpstan-ignore-line */
        $finder = new Finder();
        $finder->files()->depth('==0')->in($pathBackup)->name('*.sql');
        $finder->sortByChangedTime()->reverseSorting();

        return $this->render('manage/config-index.html.twig', [
            'filesBackup' => $finder,
        ]);
    }

    #[Route(path: '/manage/restore/{dump}', name: 'manage_config__restore')]
    public function restoreDump(string $dump, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pathBackup = (string) $this->getParameter('olix.backup.path'); /** @phpstan-ignore-line */
            $helper = new DoctrineHelper($manager);
            $return = $helper->restoreBase(sprintf('%s/%s', $pathBackup, $dump));
            if (0 === $return) {
                $this->addFlash('success', sprintf('Le dump <strong>%s</strong> de la base a été restauré avec succès', $dump));
            } else {
                $this->addFlash('error', sprintf("Le dump <strong>%s</strong> de la base n'a pu être restauré", $dump));
            }

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('les données actuelles et restaurer le dump <b>%s</b>', $dump),
        ]);
    }
}
