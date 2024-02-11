<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Manage;

use App\Entity\Institution;
use App\Form\InstitutionType;
use App\Repository\InstitutionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des institutions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class InstitutionController extends AbstractController
{
    #[Route(path: '/manage/institution', name: 'manage_institution__index')]
    public function index(InstitutionRepository $repository): Response
    {
        return $this->render('manage/institution-index.html.twig', [
            'institutions' => $repository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route(path: '/manage/institution/create', name: 'manage_institution__create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $institution = new Institution();
        $form = $this->createForm(InstitutionType::class, $institution);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logo */
            $logo = $form->get('image')->getData();
            $image = $this->getLogoBase64($logo);
            if (null !== $image) {
                $institution->setLogo($image);
            }

            $entityManager->persist($institution);
            $entityManager->flush();
            $this->addFlash('success', sprintf("La création de l'organisme <strong>%s</strong> a bien été prise en compte", $institution));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Créer une nouvelle institution',
            ],
        ]);
    }

    #[Route(path: '/manage/institution/edit/{id}', name: 'manage_institution__edit', methods: ['GET', 'POST'])]
    public function update(Request $request, Institution $institution, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InstitutionType::class, $institution);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logo */
            $logo = $form->get('image')->getData();
            $image = $this->getLogoBase64($logo);
            if (null !== $image) {
                $institution->setLogo($image);
            }

            $entityManager->flush();
            $this->addFlash('success', sprintf("La modification de l'organisme <strong>%s</strong> a bien été prise en compte", $institution));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-vertical.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => 'Modifier une institution',
            ],
        ]);
    }

    /**
     * Retoune le fichier téléchargé en base64.
     *
     * @param UploadedFile $logo
     */
    private function getLogoBase64(?UploadedFile $logo): ?string
    {
        if (!$logo instanceof UploadedFile) {
            return null;
        }

        $filename = 'symfony-temp-'.uniqid().'.'.$logo->guessExtension();

        // Déplace le fichier dans le dossier temporaire
        try {
            $logo->move(sys_get_temp_dir(), $filename);
        } catch (FileException) {
            // ... handle exception if something happens during file upload
            return null;
        }

        // Récupère le contenu et le transforme en Base64
        $content = file_get_contents(sys_get_temp_dir().'/'.$filename);
        if (false === $content) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($content);
    }
}
