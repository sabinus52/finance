<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

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
class ManageCategoryController extends AbstractController
{
    /**
     * @Route("/manage/category", name="manage_category__index")
     */
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/category-index.html.twig', [
            'recettes' => $categoryRepository->findLevel1ByType(true),
            'depenses' => $categoryRepository->findLevel1ByType(false),
        ]);
    }

    /**
     * @Route("/manage/category/1/create/{type}", name="manage_category__create1", methods={"GET", "POST"})
     */
    public function createCat(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $type = (int) $request->get('type', 1);
        $category->setType((bool) $type);
        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('manage_category__create1', ['type' => $type]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'La création de la catégorie <strong>'.$category->getName().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('manage/category-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/manage/category/2/create/{id}", name="manage_category__create2", methods={"GET", "POST"})
     */
    public function createSubCat(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $subCategory = new Category();
        $form = $this->createForm(CategoryType::class, $subCategory, [
            'action' => $this->generateUrl('manage_category__create2', ['id' => $category->getId()]),
        ]);
        $form->remove('type');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $subCategory->setType($category->getType());
            $subCategory->setParent($category);
            $entityManager->persist($subCategory);
            $entityManager->flush();
            $this->addFlash('success', 'La création de la catégorie <strong>'.$subCategory->getName().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('manage/category-create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/manage/category/edit/{id}", name="manage_category__edit", methods={"GET", "POST"})
     */
    public function update(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('manage_category__edit', ['id' => $category->getId()]),
        ]);
        $form->remove('type');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La modification de la catégorie <strong>'.$category->getName().'</strong> a bien été prise en compte');

            return new Response('OK');
        }

        return $this->renderForm('manage/category-update.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }
}
