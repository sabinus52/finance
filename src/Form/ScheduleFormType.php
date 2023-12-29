<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Schedule;
use DateTimeImmutable;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'une planification.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ScheduleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $date = new DateTimeImmutable();
        $builder
            ->add('doAt', DatePickerType::class, [
                'label' => 'Date',
                'format' => 'dd/MM/yyyy',
                'input' => 'datetime_immutable',
                'js_min_date' => $date->modify('+ 1 day')->format('Y-m-d'),
                'required' => false,
            ])
            ->add('frequency', IntegerType::class, [
                'label' => 'Fréquence',
                'required' => false,
            ])
            ->add('period', ChoiceType::class, [
                'label' => 'Période',
                'required' => true,
                'choices' => [
                    'Jour' => 'D',
                    'Semaine' => 'W',
                    'Mois' => 'M',
                    'Année' => 'Y',
                ],
            ])
            ->add('number', IntegerType::class, [
                'label' => 'Après X postage',
                'help' => 'Si nombre de mensualités connues',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Schedule::class,
        ]);
    }
}
