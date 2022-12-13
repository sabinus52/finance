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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'une valorisation.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ValorisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DatePickerType::class, [
                'label' => 'Date',
                'format' => 'dd/MM/yyyy',
                'disabled' => true,
                'help' => 'Choisir n\'importe quel jour du mois',
            ])
            ->add('balance', MoneyType::class, [
                'label' => 'Solde',
                'required' => false,
                'help' => 'Mettre la valorisation totale. La variation sera calculée automatiquement.', 
            ])
            ->add('account', EntityType::class, [
                'label' => 'Compte',
                'disabled' => true,
                'class' => Account::class,
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->where('acc.type >= 50')
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
                'empty_data' => null,
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
