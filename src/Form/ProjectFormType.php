<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Project;
use App\Values\ProjectCategory;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Olix\BackOfficeBundle\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'un projet.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => ProjectCategory::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('startedAt', DatePickerType::class, [
                'label' => 'Date de début',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('finishAt', DatePickerType::class, [
                'label' => 'Date de fin',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('state', SwitchType::class, [
                'label' => 'Statut',
                'js_on_text' => 'OUVERT',
                'js_off_text' => 'CLOS',
                'js_on_color' => 'success',
                'js_off_color' => 'danger',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
