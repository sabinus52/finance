<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\Model;
use App\Entity\Recipient;
use App\Entity\Schedule;
use App\Form\ModelStandardFormType;
use App\Form\ModelTransferFormType;
use App\Form\ScheduleFormType;
use App\Repository\ModelRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des modèles et transactions planifiées.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModelController extends AbstractController
{
    #[Route(path: '/manage/model', name: 'manage_model__index')]
    public function index(ModelRepository $repository): Response
    {
        return $this->render('manage/model-index.html.twig', [
            'models' => $repository->findAll(),
        ]);
    }

    #[Route(path: '/manage/model/create/{type}', name: 'manage_model__create', methods: ['GET', 'POST'])]
    public function create(Request $request, string $type, EntityManagerInterface $entityManager): Response
    {
        $model = $this->createModel($entityManager, $type);
        $form = $this->getFormModel($model, (bool) $type);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($model);
            $entityManager->flush();
            $this->addFlash('success', sprintf('La création du modèle <strong>%s</strong> a bien été prise en compte', $model));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau modèle',
            ],
        ]);
    }

    #[Route(path: '/manage/model/edit/{id}', name: 'manage_model__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $form = $this->getFormModel($model, $model->getAmount() > 0);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('La modification du modèle <strong>%s</strong> a bien été prise en compte', $model));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un modèle',
            ],
        ]);
    }

    #[Route(path: '/account/model/remove/{id}', name: 'manage_model__remove', methods: ['GET', 'POST'])]
    public function remove(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($model);
            $entityManager->flush();
            $this->addFlash('success', sprintf('La suppression du modèle <strong>%s</strong> a bien été prise en compte', $model));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-content-delete.html.twig', [
            'form' => $form,
            'element' => sprintf('ce modèle <b>%s</b>', $model),
        ]);
    }

    #[Route(path: '/manage/model/schedule/{id}', name: 'manage_model__schedule', methods: ['GET', 'POST'])]
    public function updateSchedule(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();
        if (null === $schedule) {
            $schedule = new Schedule();
        }
        $form = $this->createForm(ScheduleFormType::class, $schedule);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $model->setSchedule($schedule);
            $entityManager->flush();
            $this->addFlash('success', sprintf('La modification de la planification du modèle <strong>%s</strong> a bien été prise en compte', $model));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier une planification',
            ],
        ]);
    }

    #[Route(path: '/manage/model/schedule/enable/{id}', name: 'manage_model__schedule_enable', methods: ['GET', 'POST'])]
    public function enableSchedule(Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();
        if (null === $schedule) {
            $this->addFlash('warning', 'Aucune planification associé à ce modèle');

            return $this->redirectToRoute('manage_model__index');
        }
        $schedule->setState(true);
        // Réactive en remettant la prochaine date de la planification
        $period = new \DateInterval(sprintf('P%s%s', $schedule->getFrequency(), $schedule->getPeriod()));
        while ($schedule->getDoAt()->format('Y-m-d') < date('Y-m-d')) {
            $schedule->setDoAt($schedule->getDoAt()->add($period));
        }
        $entityManager->flush();
        $this->addFlash('success', sprintf('La planification du modèle <strong>%s</strong> a été activée', $model));

        return $this->redirectToRoute('manage_model__index');
    }

    #[Route(path: '/manage/model/schedule/disable/{id}', name: 'manage_model__schedule_disable', methods: ['GET', 'POST'])]
    public function disableSchedule(Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();
        if (null === $schedule) {
            $this->addFlash('warning', 'Aucune planification associé à ce modèle');

            return $this->redirectToRoute('manage_model__index');
        }
        $schedule->setState(false);
        $entityManager->flush();
        $this->addFlash('success', sprintf('La planification du modèle <strong>%s</strong> a été désactivée', $model));

        return $this->redirectToRoute('manage_model__index');
    }

    #[Route(path: '/manage/model/schedule/remove/{id}', name: 'manage_model__schedule_remove', methods: ['GET', 'POST'])]
    public function removeSchedule(Model $model, EntityManagerInterface $entityManager): Response
    {
        $schedule = $model->getSchedule();
        if (null === $schedule) {
            $this->addFlash('warning', 'Aucune planification associé à ce modèle');

            return $this->redirectToRoute('manage_model__index');
        }
        $model->setSchedule(null);
        $entityManager->remove($schedule);
        $entityManager->flush();
        $this->addFlash('success', sprintf('La planification du modèle <strong>%s</strong> a été supprimée', $model));

        return $this->redirectToRoute('manage_model__index');
    }

    /**
     * Crée et initialise le nouveau medèle en fonction de son type.
     *
     * @return Model
     */
    private function createModel(EntityManagerInterface $entityManager, string $type): Model
    {
        $model = new Model();

        if (Category::VIREMENT === $type) {
            $model->setType(new TransactionType(TransactionType::TRANSFER));
            $model->setPayment(new Payment(Payment::INTERNAL));
            $recipient = $entityManager->getRepository(Recipient::class)->findInternal(); /** @phpstan-ignore-line */
            $model->setRecipient($recipient);
        } else {
            if ('1' !== $type && '0' !== $type) {
                throw new \Exception('Type de modèle inconnu');
            }
        }

        return $model;
    }

    /**
     * Retourne le formulaire en fonction de son type.
     *
     * @return FormInterface
     */
    private function getFormModel(Model $model, bool $income): FormInterface
    {
        $options = [];
        if (TransactionType::TRANSFER === $model->getTypeValue()) {
            $options['category'] = sprintf('cat.type = 0 AND cat1.code = \'%s\'', Category::MOVEMENT);
            $formClass = ModelTransferFormType::class;
        } else {
            $options['category'] = sprintf('cat.type = %s AND (cat1.code <> \'%s\' OR cat1.code IS NULL)', (int) $income, Category::MOVEMENT);
            $formClass = ModelStandardFormType::class;
        }

        return $this->createForm($formClass, $model, $options);
    }
}
