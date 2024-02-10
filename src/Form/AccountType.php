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
use App\Entity\Institution;
use App\Repository\AccountRepository;
use App\Values\AccountType as ValuesAccountType;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
use Olix\BackOfficeBundle\Form\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'un compte.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('institution', EntityType::class, [
                'label' => 'Organisme',
                'class' => Institution::class,
                'choice_label' => 'name',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de compte',
                'choices' => ValuesAccountType::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du compte',
                'required' => false,
            ])
            ->add('shortName', TextType::class, [
                'label' => 'Nom court',
                'required' => false,
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro du compte',
                'required' => false,
            ])
            ->add('currency', CurrencyType::class, [
                'label' => 'Devise du compte',
                'required' => false,
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Groupe',
                'choices' => array_flip($options['choice_units']),
            ])
            ->add('initial', MoneyType::class, [
                'label' => 'Solde initial',
                'required' => false,
            ])
            ->add('openedAt', DatePickerType::class, [
                'label' => 'Date d\'ouverture',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('closedAt', DatePickerType::class, [
                'label' => 'Date de clôture',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ])
            ->add('overdraft', MoneyType::class, [
                'label' => 'Découvert autorisé',
                'required' => false,
            ])
            ->add('accAssoc', EntityType::class, [
                'label' => 'Compte associé',
                'class' => Account::class,
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ;
                },
                'choice_label' => function (Account $choice) {
                    $result = $choice->getFullName();
                    if (null !== $choice->getClosedAt()) {
                        $result .= ' (fermé)';
                    }

                    return $result;
                },
                'choice_attr' => function (Account $choice) {
                    if (null !== $choice->getClosedAt()) {
                        return ['class' => 'text-secondary', 'style' => 'font-style: italic;'];
                    }

                    return [];
                },
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
            'choice_units' => null,
        ]);
    }
}
