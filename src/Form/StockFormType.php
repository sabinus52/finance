<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'une cotation boursière.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class StockFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeISIN', TextType::class, [
                'label' => 'Code ISIN',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('closedAt', DatePickerType::class, [
                'label' => 'Date de clôture',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
        ;
        // Ajout de la fusion en fonction du Data chargé
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            /** @var Stock $data */
            $data = $event->getData();
            $form->add('fusionFrom', EntityType::class, [
                'label' => 'Ancien titre fusionné',
                'class' => Stock::class,
                'query_builder' => function (StockRepository $er) use ($data) {
                    return $er->createQueryBuilder('stk')
                        ->leftJoin('stk.fusionTo', 'fus')
                        ->where('stk.closedAt IS NOT NULL')
                        ->andWhere('fus.id IS NULL OR fus.id = :id')
                        ->setParameter('id', $data->getId())
                        ->orderBy('stk.name')
                    ;
                },
                'required' => false,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
