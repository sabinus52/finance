<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Entity\Account;
use App\Entity\Stock;
use App\Entity\StockPrice;
use App\Form\StockFormType;
use App\Form\StockFusionFormType;
use App\Form\StockPriceFormType;
use App\Helper\Charts\StockPriceChart;
use App\Repository\StockPriceRepository;
use App\Repository\StockRepository;
use App\WorkFlow\Balance;
use App\WorkFlow\StockFusion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des cotations boursières.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockController extends AbstractController
{
    #[Route(path: '/manage/stock', name: 'manage_stock__index')]
    public function index(StockRepository $repository): Response
    {
        return $this->render('manage/stock-index.html.twig', [
            'stocks' => $repository->findAll(),
        ]);
    }

    #[Route(path: '/manage/stock/create', name: 'manage_stock__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockFormType::class, $stock);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stock);
            $entityManager->flush();
            $this->addFlash('success', sprintf("La création de l'action <strong>%s</strong> a bien été prise en compte", $stock));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer une nouvelle action',
            ],
        ]);
    }

    #[Route(path: '/manage/stock/edit/{id}', name: 'manage_stock__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockFormType::class, $stock);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf("La modification de l'action <strong>%s</strong> a bien été prise en compte", $stock));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier une action',
            ],
        ]);
    }

    #[Route(path: '/manage/stock/prices/{id}', name: 'manage_stock__prices', methods: ['GET', 'POST'])]
    public function seeListPrices(Stock $stock, StockPriceRepository $repository): Response
    {
        $prices = $repository->findByStock($stock, ['date' => 'DESC']); /** @phpstan-ignore-line */
        $chart = new StockPriceChart();

        return $this->render('manage/stock-item.html.twig', [
            'stock' => $stock,
            'price' => [
                'last' => current($prices),
                'max' => array_reduce($prices, static fn ($a, $b) => $a ? ($a->getPrice() > $b->getPrice() ? $a : $b) : $b),
                'min' => array_reduce($prices, static fn ($a, $b) => $a ? ($a->getPrice() < $b->getPrice() ? $a : $b) : $b),
            ],
            'prices' => $prices,
            'chart' => $chart->getChart($prices),
        ]);
    }

    #[Route(path: '/manage/stock/prices/{id}/create', name: 'manage_stock__price_add', methods: ['GET', 'POST'])]
    public function createPrice(Request $request, Stock $stock, EntityManagerInterface $entityManager, StockPriceRepository $repository): Response
    {
        // Recherche la dernière cotation
        $last = $repository->findOneLastPrice($stock);
        $date = new \DateTime();
        if ($last instanceof StockPrice) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        $stockPrice = new StockPrice();
        $stockPrice->setStock($stock);
        $stockPrice->setDate($date->modify('last day of this month'));

        $form = $this->createForm(StockPriceFormType::class, $stockPrice);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stockPrice);
            $entityManager->flush();
            $balance = new Balance($entityManager);
            $balance->updateAllWallets();
            $this->addFlash('success', 'La création de la cotation de <strong>'.$stock.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer une nouvelle cotation',
                'btnlabel' => 'Ajouter',
            ],
        ]);
    }

    #[Route(path: '/manage/stock/prices/update/{id}', name: 'manage_stock__price_upd', methods: ['GET', 'POST'])]
    public function updatePrice(Request $request, StockPrice $stockPrice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockPriceFormType::class, $stockPrice);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $balance = new Balance($entityManager);
            $balance->updateAllWallets();
            $this->addFlash('success', 'La modification de la cotation de <strong>'.$stockPrice->getStock().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier la cotation',
            ],
        ]);
    }

    /**
     * Création de la fusion d'un titre boursier.
     */
    #[Route(path: '/account/{id}/fusion/stock/{stock}', name: 'manage_stock__fusion', methods: ['GET', 'POST'])]
    public function fusion(Request $request, Account $account, Stock $stock, EntityManagerInterface $entityManager, StockPriceRepository $repository): Response
    {
        $form = $this->createForm(StockFusionFormType::class, $stock);

        // Recherche la dernière cotation
        $last = $repository->findOneLastPrice($stock);
        if ($last instanceof StockPrice) {
            $form->get('price')->setData($last->getPrice());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->checkFormFusion($form)) {
            $fusion = new StockFusion($entityManager, $account);
            $fusion->setOldStock($stock, $form->get('price')->getData());
            $fusion->setNewStock($form->get('fusion2')->getData(), $form->get('name2')->getData(), $form->get('codeISIN2')->getData(), $form->get('volume2')->getData(), $form->get('price2')->getData());
            $fusion->execute();

            $this->addFlash('success', sprintf('La fusion de %s vers %s a bien été prise en compte', $stock, $fusion->getNewStock()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Fusion de %s', $stock),
                'btnlabel' => 'Fusionner',
            ],
        ]);
    }

    /**
     * Vérifie la formulaire de la fusion.
     */
    private function checkFormFusion(FormInterface $form): bool
    {
        $isValid = true;
        if (empty($form->get('fusion2')->getData()) && empty($form->get('name2')->getData())) {
            $form->get('name2')->addError(new FormError('Ce champs ne peut être vide si aucun titre n\'est sélectionné'));
            $isValid = false;
        }
        if (empty($form->get('fusion2')->getData()) && empty($form->get('codeISIN2')->getData())) {
            $form->get('codeISIN2')->addError(new FormError('Ce champs ne peut être vide si aucun titre n\'est sélectionné'));
            $isValid = false;
        }

        return $isValid;
    }
}
