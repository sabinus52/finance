<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Repository\CategoryRepository;
use App\Values\TransactionType;
use Olix\BackOfficeBundle\Form\Type\Select2EntityType;
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
class TransactionVhOtherFormType extends TransactionFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('memo')
            ->add('category', Select2EntityType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'fullname',
                'query_builder' => function (CategoryRepository $er) {
                    return $er->createQueryBuilder('cat')
                        ->addSelect('cat1')
                        ->innerJoin('cat.parent', 'cat1')
                        ->where('cat1.name IN (:name)')
                        ->setParameter('name', ['Automobile', 'Moto'])
                        ->orderBy('cat1.name')
                        ->addOrderBy('cat.name')
                    ;
                },
                'group_by' => fn (Category $category) => $category->getParent()->getName(),
                'empty_data' => null,
            ])
            ->add('transactionVehicle', TransacVehicleFormType::class, [
                'label' => 'Véhicule',
                'required' => true,
            ])
        ;
        $subForm = $builder->get('transactionVehicle');
        $subForm->remove('distance');
        $subForm->remove('volume');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'transaction_type' => TransactionType::VH_OTHER,
        ]);
    }
}
