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
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des catégories.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class CategoryController extends AbstractController
{
    #[Route(path: '/manage/category', name: 'manage_category__index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/category-index.html.twig', [
            'recettes' => $categoryRepository->findLevel1ByType(true),
            'depenses' => $categoryRepository->findLevel1ByType(false),
        ]);
    }

    #[Route(path: '/manage/category/1/create/{type}', name: 'manage_category__create1', methods: ['GET', 'POST'])]
    public function createCat(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $type = (int) $request->get('type', 1);
        $category->setType((bool) $type);
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'La création de la catégorie <strong>'.$category.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer une nouvelle catégorie',
            ],
        ]);
    }

    #[Route(path: '/manage/category/2/create/{id}', name: 'manage_category__create2', methods: ['GET', 'POST'])]
    public function createSubCat(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $subCategory = new Category();
        $form = $this->createForm(CategoryType::class, $subCategory);
        $form->remove('type');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $subCategory->setType($category->getType());
            $subCategory->setParent($category);
            $entityManager->persist($subCategory);
            $entityManager->flush();
            $this->addFlash('success', 'La création de la catégorie <strong>'.$subCategory.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer une sous-catégorie',
            ],
        ]);
    }

    #[Route(path: '/manage/category/edit/{id}', name: 'manage_category__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Catégorie réservée pour les virements internes
        if (Category::VIREMENT === $category->getCode()) {
            return $this->render('@OlixBackOffice/Include/modal-alert.html.twig', [
                'message' => 'Cette catégorie <strong>'.$category.'</strong> ne peut pas être modifiée.',
            ]);
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->remove('type');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification de la catégorie <strong>'.$category.'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier une catégorie',
            ],
        ]);
    }
}
