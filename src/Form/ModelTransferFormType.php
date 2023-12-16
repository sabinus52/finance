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
use App\Entity\Model;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use Olix\BackOfficeBundle\Form\Type\Select2EntityType;
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
 */
class ModelTransferFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'required' => false,
            ])
            ->add('category', Select2EntityType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'fullname',
                'query_builder' => function (CategoryRepository $er) use ($options) {
                    return $er->createQueryBuilder('cat')
                        ->addSelect('cat1')
                        ->innerJoin('cat.parent', 'cat1')
                        ->where($options['category'])
                        ->orderBy('cat1.name')
                        ->addOrderBy('cat.name')
                    ;
                },
                'group_by' => fn (Category $category) => $category->getParent()->getName(),
                'empty_data' => null,
            ])
            ->add('account', EntityType::class, [
                'label' => 'Du compte',
                'required' => false,
                'class' => Account::class,
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->where('acc.closedAt IS NULL')
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ;
                },
                'empty_data' => null,
            ])
            ->add('transfer', EntityType::class, [
                'label' => 'Vers le compte',
                'required' => false,
                'class' => Account::class,
                'query_builder' => function (AccountRepository $er) {
                    return $er->createQueryBuilder('acc')
                        ->addSelect('ist')
                        ->innerJoin('acc.institution', 'ist')
                        ->where('acc.closedAt IS NULL')
                        ->orderBy('ist.name')
                        ->addOrderBy('acc.name')
                    ;
                },
                'constraints' => new NotBlank(),
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
            'data_class' => Model::class,
            'category' => '0=0',
        ]);
    }
}
