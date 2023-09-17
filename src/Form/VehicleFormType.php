<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Vehicle;
use App\Values\Fuel;
use App\Values\VehicleType;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'un véhicule.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class VehicleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'required' => false,
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de véhicule',
                'choices' => VehicleType::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
            ])
            ->add('fuel', ChoiceType::class, [
                'label' => 'Carburant',
                'choices' => Fuel::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
            ])
            ->add('matriculation', TextType::class, [
                'label' => 'Immatriculation',
                'required' => false,
            ])
            ->add('registeredAt', DatePickerType::class, [
                'label' => 'Date d\'immatriculation',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('boughtAt', DatePickerType::class, [
                'label' => 'Date d\'achat',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('soldAt', DatePickerType::class, [
                'label' => 'Date de vente',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
