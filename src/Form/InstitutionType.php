<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Institution;
use Olix\BackOfficeBundle\Form\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Formulaire d'un organisme.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class InstitutionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'organisme',
                'required' => false,
            ])
            ->add('link', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'minHeight' => 64,
                        'maxWidth' => 64,
                        'maxRatio' => 1,
                        'minRatio' => 1,
                        'mimeTypes' => [
                            'image/png',
                        ],
                        'maxHeightMessage' => 'Uniquement des images au format png max 64x64 px',
                        'maxWidthMessage' => 'Uniquement des images au format png max 64x64 px',
                        'maxRatioMessage' => 'Uniquement des images au format png max 64x64 px',
                        'minRatioMessage' => 'Uniquement des images au format png max 64x64 px',
                        'mimeTypesMessage' => 'Uniquement des images au format png max 64x64 px',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Institution::class,
        ]);
    }
}
