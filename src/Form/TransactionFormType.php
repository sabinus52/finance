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
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\RecipientRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Olix\BackOfficeBundle\Form\Type\Select2EntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'une opération.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionFormType extends AbstractType
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
            ->add('account', EntityType::class, [
                'label' => 'Compte',
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
                'empty_data' => null,
            ])
            ->add('payment', ChoiceType::class, [
                'label' => 'Paiement',
                'required' => false,
                'choices' => Payment::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
                'empty_data' => null,
            ])
            ->add('recipient', Select2EntityType::class, [
                'label' => 'Tiers',
                'required' => false,
                'class' => Recipient::class,
                'choice_label' => 'name',
                'query_builder' => function (RecipientRepository $er) {
                    return $er->createQueryBuilder('rpt')
                        ->orderBy('rpt.name')
                    ;
                },
                'empty_data' => null,
            ])
            ->add('category', Select2EntityType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'fullname',
                'query_builder' => function (CategoryRepository $er) {
                    return $er->createQueryBuilder('cat')
                        ->addSelect('cat1')
                        ->innerJoin('cat.parent', 'cat1')
                        ->orderBy('cat1.name')
                        ->addOrderBy('cat.name')
                    ;
                },
                'group_by' => fn (Category $category) => $category->getParent()->getName(),
                'empty_data' => null,
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
            'transaction_type' => TransactionType::STANDARD,
        ]);
    }
}
