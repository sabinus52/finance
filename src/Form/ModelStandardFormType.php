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
use App\Entity\Recipient;
use App\Entity\Vehicle;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\RecipientRepository;
use App\Repository\VehicleRepository;
use App\Values\Payment;
use Olix\BackOfficeBundle\Form\Type\Select2EntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'un modèle.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModelStandardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('account', EntityType::class, [
                'label' => 'Compte bancaire',
                'required' => false,
                'class' => Account::class,
                'query_builder' => static fn (AccountRepository $er) => $er->createQueryBuilder('acc')
                    ->addSelect('ist')
                    ->innerJoin('acc.institution', 'ist')
                    ->where('acc.closedAt IS NULL')
                    ->orderBy('ist.name')
                    ->addOrderBy('acc.name'),
                'empty_data' => null,
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'required' => false,
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
                'query_builder' => static fn (RecipientRepository $er) => $er->createQueryBuilder('rpt')
                    ->orderBy('rpt.name'),
                'empty_data' => null,
            ])
            ->add('category', Select2EntityType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'fullname',
                'query_builder' => static fn (CategoryRepository $er) => $er->createQueryBuilder('cat')
                    ->addSelect('cat1')
                    ->innerJoin('cat.parent', 'cat1')
                    ->where($options['category'])
                    ->orderBy('cat1.name')
                    ->addOrderBy('cat.name'),
                'group_by' => static fn (Category $category) => $category->getParent()->getName(),
                'empty_data' => null,
            ])
            ->add('memo', TextType::class, [
                'label' => 'Mémo',
                'required' => false,
            ])
            ->add('vehicle', EntityType::class, [
                'label' => 'Véhicule associé',
                'required' => false,
                'class' => Vehicle::class,
                'choice_label' => 'name',
                'query_builder' => static fn (VehicleRepository $er) => $er->createQueryBuilder('vhc')
                    ->where('vhc.soldAt IS NULL')
                    ->orderBy('vhc.brand')
                    ->addOrderBy('vhc.model'),
                'empty_data' => null,
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
