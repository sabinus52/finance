<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire d'un virement.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DatePickerType::class, [
                'label' => 'Date',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'required' => false,
            ])
            ->add('source', EntityType::class, [
                'label' => 'De',
                'required' => false,
                'class' => Account::class,
                'choice_label' => 'fullname',
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ; // TODO uniquement que les comptes ouverts
                },
                'constraints' => [new NotBlank()],
                'empty_data' => null,
                'mapped' => false,
            ])
            ->add('target', EntityType::class, [
                'label' => 'Vers',
                'required' => false,
                'class' => Account::class,
                'choice_label' => 'fullname',
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ; // TODO uniquement que les comptes ouverts
                },
                'constraints' => [new NotBlank()],
                'empty_data' => null,
                'mapped' => false,
            ])
            ->add('memo', TextType::class, [
                'label' => 'Mémo',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
