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
use Olix\BackOfficeBundle\Form\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Formulaire d'une fusion d'un titre boursier.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockFusionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'disabled' => true,
            ])
            ->add('closedAt', DatePickerType::class, [
                'label' => 'Date de fusion',
                'format' => 'dd/MM/yyyy',
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Valeur de clôture',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('fusion2', EntityType::class, [
                'class' => Stock::class,
            ])
            ->add('name2', TextType::class, [
                'label' => 'Nouveau nom du titre',
                'required' => false,
                'mapped' => false,
                'help' => 'A remplir si non existant dans la liste ci-dessus',
                'constraints' => [
                    new Length(['max' => 100]),
                ],
            ])
            ->add('codeISIN2', TextType::class, [
                'label' => 'Nouveau code ISIN',
                'required' => false,
                'mapped' => false,
                'help' => 'A remplir si non existant dans la liste ci-dessus',
                'constraints' => [
                    new Length(['max' => 12]),
                ],
            ])
            ->add('volume2', NumberType::class, [
                'label' => 'Quantité fusionnée',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                ],
            ])
            ->add('price2', MoneyType::class, [
                'label' => 'Valeur d\'ouverture',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $form = $event->getForm();
            /** @var Stock $data */
            $data = $event->getData();
            $form->add('fusion2', EntityType::class, [
                'label' => 'Titre fusionné',
                'class' => Stock::class,
                'query_builder' => static fn (StockRepository $er) => $er->createQueryBuilder('stk')
                    ->leftJoin('stk.fusionTo', 'fus')
                    ->where('stk.closedAt IS NULL')
                    ->andWhere('stk.id <> :id')
                    ->setParameter('id', $data->getId())
                    ->orderBy('stk.name'),
                'required' => false,
                'mapped' => false,
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
