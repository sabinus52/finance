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
            ->add('number', TextType::class, [
                'label' => 'Numéro du compte',
                'required' => false,
            ])
            ->add('currency', CurrencyType::class, [
                'label' => 'Devise du compte',
                'required' => false,
            ])
            ->add('balance', MoneyType::class, [
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

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
        ]);
    }
}
