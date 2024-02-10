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
use Olix\BackOfficeBundle\Form\Type\SwitchType;
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
class TransferFormType extends AbstractType
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
            ->add('invest', MoneyType::class, [
                'label' => 'Montant investi',
                'required' => false,
                'mapped' => false,
            ])
            ->add('purchase', SwitchType::class, [
                'label' => 'Rachat total',
                'required' => false,
                'mapped' => false,
            ])
            ->add('source', EntityType::class, $this->getOptionsAccount('De', $options['filter']['source'], $options['isNew']))
            ->add('target', EntityType::class, $this->getOptionsAccount('Vers', $options['filter']['target'], $options['isNew']))
            ->add('memo', TextType::class, [
                'label' => 'Mémo',
                'required' => false,
            ])
        ;

        // Suppression des champs du formulaire principal
        if (isset($options['filter']['!fields'])) {
            foreach ($options['filter']['!fields'] as $field) {
                $builder->remove($field);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'isNew' => false,
            'filter' => [],
        ]);
    }

    /**
     * Retourne les options du champs du compte source et cible.
     *
     * @param string $label label du champs
     * @param string $where Clause de filtre des comptes
     * @param bool   $isNew Si création ou mis à jour
     *
     * @return array<mixed>
     */
    private function getOptionsAccount(string $label, string $where, bool $isNew): array
    {
        return [
            'label' => $label,
            'required' => false,
            'class' => Account::class,
            'query_builder' => static function (AccountRepository $repository) use ($where, $isNew) {
                $query = $repository->createQueryBuilder('acc')
                    ->addSelect('ist')
                    ->innerJoin('acc.institution', 'ist')
                    ->where($where)
                    ->orderBy('ist.name')
                    ->addOrderBy('acc.name')
                ;
                if ($isNew) {
                    $query->andWhere('acc.closedAt IS NULL');
                }

                return $query;
            },
            'choice_label' => static function (Account $choice) {
                $result = $choice->getFullName();
                if (null !== $choice->getClosedAt()) {
                    $result .= ' (fermé)';
                }

                return $result;
            },
            'choice_attr' => static function (Account $choice) {
                if (null !== $choice->getClosedAt()) {
                    return ['class' => 'text-secondary', 'style' => 'font-style: italic;'];
                }

                return [];
            },
            'constraints' => [new NotBlank()],
            'empty_data' => null,
            'mapped' => false,
        ];
    }
}
