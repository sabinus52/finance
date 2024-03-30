<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Report;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\Vehicle;
use App\Form\TransactionVehicleFormType;
use App\Form\VehicleFormType;
use App\Transaction\TransactionModelInterface;
use App\Transaction\TransactionModelRouter;
use App\Values\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controleur de la gestion des véhicules.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VehicleManagerController extends AbstractController
{
    /**
     * Création d'un nouveau véhicule.
     */
    #[Route(path: '/rapports/vehicules/creation', name: 'report_vehicle__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transacModelRouter = new TransactionModelRouter($entityManager);
        $transactionModel = $transacModelRouter->createVehicle(Category::VEHICULEFUNDING);
        $form = $this->prepareFormCreate($transactionModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $transactionModel->checkForm($form)) {
            /** @var Vehicle $vehicle */
            $vehicle = $form->get('vehicle')->getData();
            /** @var Transaction $transaction */
            $transaction = $form->get('transaction')->getData();

            $transactionModel
                ->setTransaction($transaction)
                ->setDatas([
                    'transactionVehicle' => [
                        'vehicle' => $vehicle,
                    ],
                ])
            ;

            $entityManager->persist($vehicle);
            $transactionModel->insert($form);

            $this->addFlash('success', sprintf('La création du véhicule <strong>%s</strong> a bien été prise en compte', $vehicle));

            return new Response('OK');
        }

        return $this->render('report/vehicle-create-modal.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Modification d'un véhicule.
     */
    #[Route(path: '/rapports/vehicules/modification/{id}', name: 'report_vehicle__edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehicleFormType::class, $vehicle);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('La modification du véhicule <strong>%s</strong> a bien été prise en compte', $vehicle));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier un véhicule',
            ],
        ]);
    }

    /**
     * Création d'une transaction de financement de véhicule.
     */
    #[Route(path: '/rapports/vehicle/achat/{id}', name: 'report_vehicle__buy', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function buy(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $vehicle, $router->createVehicle(Category::VEHICULEFUNDING));
    }

    /**
     * Création d'une transaction de revente de véhicule.
     */
    #[Route(path: '/rapports/vehicle/vente/{id}', name: 'report_vehicle__sale', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function sale(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $router = new TransactionModelRouter($entityManager);

        return $this->createTransaction($request, $vehicle, $router->createVehicle(Category::RESALE));
    }

    /**
     * Création du formulaire de la transaction.
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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            // Si revente, on flage comme quoi le véhicule a été vendu
            if (Category::RESALE === $transaction->getCategory()->getCode()) {
                $vehicle->setSoldAt($transaction->getDate());
            }
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('La création %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Créer %s', $modelTransaction->getFormTitle()),
            ],
        ]);
    }

    /**
     * Prépare le formulaire de l'achat d'un nouveau véhicule.
     */
    private function prepareFormCreate(TransactionModelInterface $transactionModel): FormInterface
    {
        $vehicle = new Vehicle();
        $transaction = new Transaction();

        // Création du formulaire de base
        $form = $this->createFormBuilder()
            ->add('vehicle', VehicleFormType::class)
            ->add('transaction', TransactionVehicleFormType::class, [
                'filter' => [
                    'account' => sprintf('acc.type <= %s AND acc.closedAt IS NULL', AccountType::COURANT * 10 + 9),
                    '!fields' => ['category', 'project', 'transactionVehicle'],
                ],
            ])
            ->getForm()
        ;

        // Initialisation du formulaire du véhicule
        $vehicle->setBoughtAt(new \DateTimeImmutable());
        $form->get('vehicle')->setData($vehicle);
        $form->get('vehicle')->remove('boughtAt');
        $form->get('vehicle')->remove('soldAt');

        // Initialisation du formulaire de la transaction
        $transaction = $transactionModel->getTransaction();
        $transaction->getTransactionVehicle()->setVehicle($vehicle);
        $form->get('transaction')->setData($transaction);

        return $form;
    }
}
