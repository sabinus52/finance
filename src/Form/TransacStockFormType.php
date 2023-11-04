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
use App\Entity\Stock;
use App\Entity\TransactionStock;
use App\Repository\AccountRepository;
use App\Repository\StockRepository;
use App\Values\AccountType;
use App\Values\StockPosition;
use Olix\BackOfficeBundle\Form\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
class TransacStockFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stock', EntityType::class, [
                'label' => ' ',
                'required' => false,
                'class' => Stock::class,
                'choice_label' => 'name',
                'query_builder' => function (StockRepository $er) {
                    return $er->createQueryBuilder('sck')
                        ->orderBy('sck.name')
                    ;
                },
                'empty_data' => null,
            ])
            ->add('account', EntityType::class, [
                'label' => 'Compte titre',
                'required' => false,
                'class' => Account::class,
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->where(sprintf('acc.type >= %s AND acc.type <= %s', AccountType::EPARGNE_FINANCIERE * 10, AccountType::EPARGNE_FINANCIERE * 10 + 9))
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ;
                },
                'empty_data' => null,
            ])
            ->add('position', ChoiceType::class, [
                'label' => 'Position',
                'required' => false,
                'choices' => StockPosition::getChoices(),
                'choice_label' => 'label',
                'choice_value' => 'value',
                'empty_data' => null,
            ])
            ->add('volume', NumberType::class, [
                'label' => 'Quantité',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix unitaire',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'float']),
                ],
            ])
            ->add('fee', MoneyType::class, [
                'label' => 'Commission',
                'required' => false,
                'constraints' => [
                    new Type(['type' => 'float']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransactionStock::class,
        ]);
    }
}
