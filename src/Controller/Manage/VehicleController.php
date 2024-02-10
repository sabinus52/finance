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
use App\Entity\Vehicle;
use App\Form\VehicleFormType;
use App\Repository\VehicleRepository;
use App\Transaction\TransactionModelInterface;
use App\Transaction\TransactionModelRouter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des véhicules.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class VehicleController extends AbstractController
{
    #[Route(path: '/manage/vehicle', name: 'manage_vehicle__index')]
    public function index(VehicleRepository $repository): Response
    {
        return $this->render('manage/vehicle-index.html.twig', [
            'vehicles' => $repository->findAll(),
            'modal' => [
                'class' => 'modal-lg',
            ],
        ]);
    }

    #[Route(path: '/manage/vehicle/create', name: 'manage_vehicle__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleFormType::class, $vehicle);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicle);
            $entityManager->flush();
            $this->addFlash('success', 'La création du véhicule <strong>'.$vehicle.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer un nouveau véhicule',
            ],
        ]);
    }

    #[Route(path: '/manage/vehicle/edit/{id}', name: 'manage_vehicle__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehicleFormType::class, $vehicle);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification du véhicule <strong>'.$vehicle.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un véhicule',
            ],
        ]);
    }

    /**
     * Création d'une transaction de financement de véhicule.
     */
    #[Route(path: '/manage/vehicle/buy/{id}', name: 'manage_vehicle__buy', methods: ['GET', 'POST'])]
    public function buy(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $vehicle, $router->createVehicle(Category::VEHICULEFUNDING));
    }

    /**
     * Création d'une transaction de revente de véhicule.
     */
    #[Route(path: '/manage/vehicle/sale/{id}', name: 'manage_vehicle__sale', methods: ['GET', 'POST'])]
    public function sale(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $vehicle, $router->createVehicle(Category::RESALE));
    }

    /**
     * Création du formulaire de la transaction.
     *
     * @return Response
     */
    private function createTransaction(Request $request, Vehicle $vehicle, TransactionModelInterface $modelTransaction): Response
    {
        $modelTransaction->setDatas([
            'transactionVehicle' => [
                'vehicle' => $vehicle,
            ],
        ]);

        $transaction = $modelTransaction->getTransaction();
        $form = $this->createForm($modelTransaction->getFormClass(), $transaction, $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($transaction->getAccount());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('La création %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->renderForm('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Créer %s', $modelTransaction->getFormTitle()),
            ],
        ]);
    }
}
