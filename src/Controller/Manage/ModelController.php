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
use App\Form\ModelStandardFormType;
use App\Form\ModelTransferFormType;
use App\Repository\ModelRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    /**
     * @Route("/manage/model", name="manage_model__index")
     */
    public function index(ModelRepository $repository): Response
    {
        return $this->render('manage/model-index.html.twig', [
            'models' => $repository->findAll(),
        ]);
    }

    /**
     * @Route("/manage/model/create/{type}", name="manage_model__create", methods={"GET", "POST"})
     */
    public function create(Request $request, string $type, EntityManagerInterface $entityManager): Response
    {
        $model = $this->createModel($entityManager, $type);
        $form = $this->getFormModel($model, (bool) $type);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($model);
            $entityManager->flush();
            $this->addFlash('success', 'La création du modèle <strong>'.$model.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau modèle',
            ],
        ]);
    }

    /**
     * @Route("/manage/model/edit/{id}", name="manage_model__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Model $model, EntityManagerInterface $entityManager): Response
    {
        $form = $this->getFormModel($model, ($model->getAmount() > 0));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du modèle <strong>'.$model.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un modèle',
            ],
        ]);
    }

    /**
     * Crée et initialise le nouveau medèle en fonction de son type.
     *
     * @param EntityManagerInterface $entityManager
     * @param string                 $type
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
                throw new Exception('Type de modèle inconnu');
            }
        }

        return $model;
    }

    /**
     * Retourne le formulaire en fonction de son type.
     *
     * @param Model $model
     * @param bool  $income
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
            $options['category'] = sprintf('cat.type = %s AND (cat1.code <> \'%s\' OR cat1.code IS NULL)', (int) ($income), Category::MOVEMENT);
            $formClass = ModelStandardFormType::class;
        }

        return $this->createForm($formClass, $model, $options);
    }
}
