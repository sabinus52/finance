<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\TransactionVehicle;
use App\Entity\Vehicle;
use App\Repository\VehicleRepository;
use Olix\BackOfficeBundle\Form\Type\IntegerType;
use Olix\BackOfficeBundle\Form\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Formulaire d'une opération.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransacVehicleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicle', EntityType::class, [
                'label' => ' ',
                'required' => false,
                'class' => Vehicle::class,
                'choice_label' => 'name',
                'query_builder' => function (VehicleRepository $er) {
                    return $er->createQueryBuilder('vhc')
                        ->orderBy('vhc.brand')
                        ->addOrderBy('vhc.model')
                    ;
                },
                'empty_data' => null,
            ])
            ->add('distance', IntegerType::class, [
                'label' => 'D =',
                'right_symbol' => 'Km',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'int']),
                ],
            ])
            ->add('volume', NumberType::class, [
                'label' => 'V =',
                'right_symbol' => 'L',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransactionVehicle::class,
        ]);
    }
}
