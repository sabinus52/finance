<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Entity\Model;
use App\Transaction\TransactionModelRouter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlleur des planifications.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ScheduleController extends AbstractController
{
    /**
     * Valide une planification en créant la transaction associée.
     *
     * @Route("/schedule/valid/{id}", name="schedule_valid")
     */
    public function valid(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();

        // Créer la transaction
        $router = new TransactionModelRouter($entityManager);
        $modelTransaction = $router->createFromModel($model);
        $form = $this->createForm($modelTransaction->getFormClass(), $modelTransaction->getTransaction(), $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($model->getAccount());
            $form->get('target')->setData($model->getTransfer());
        }
        $modelTransaction->insert($form);
        $this->addFlash('success', sprintf('La planification <strong>%s</strong> pour le <strong>%s</strong> a été validée', $model, $schedule->getDoAt()->format('d/m/Y')));

        // Prochaine planification
        $schedule->setNextDoAt();
        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Valide une planification en créant la transaction associée via le formulaire.
     *
     * @Route("/schedule/checkvalid/{id}", name="schedule_checkvalid")
     */
    public function checkAndValid(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();

        // Créer la transaction
        $router = new TransactionModelRouter($entityManager);
        $modelTransaction = $router->createFromModel($model);
        $form = $this->createForm($modelTransaction->getFormClass(), $modelTransaction->getTransaction(), $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($model->getAccount());
            $form->get('target')->setData($model->getTransfer());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('La planification <strong>%s</strong> pour le <strong>%s</strong> a été validée', $model, $schedule->getDoAt()->format('d/m/Y')));

            // Prochaine planification
            $schedule->setNextDoAt();
            $entityManager->flush();

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Valider %s', $modelTransaction->getFormTitle()),
            ],
        ]);
    }

    /**
     * Ignore cette planification.
     *
     * @Route("/schedule/skip/{id}", name="schedule_skip")
     */
    public function skip(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();
        $schedule->setNextDoAt();
        $entityManager->flush();
        $this->addFlash('warning', sprintf('La planification <strong>%s</strong> a été ignorée', $model));

        return $this->redirect($request->headers->get('referer'));
    }
}
