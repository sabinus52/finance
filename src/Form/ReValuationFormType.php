<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Transaction;
use Olix\BackOfficeBundle\Form\Type\DatePickerType;
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
class ReValuationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DatePickerType::class, [
                'label' => 'Date',
                'format' => 'dd/MM/yyyy',
                'disabled' => true,
                'help' => "Choisir n'importe quel jour du mois",
            ])
            ->add('balance', MoneyType::class, [
                'label' => 'Solde',
                'required' => false,
                'help' => 'Mettre la valorisation totale. La variation sera calculée automatiquement.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'isNew' => false,
            'filter' => [],
        ]);
    }
}
