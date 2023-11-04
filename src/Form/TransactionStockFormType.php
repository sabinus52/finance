<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Formulaire d'une opération boursière.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TransactionStockFormType extends TransactionStandardFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->add('transactionStock', TransacStockFormType::class, [
            'label' => 'Titre',
            'required' => true,
        ]);

        // Suppression des champs du formulaire du véhicule
        $subForm = $builder->get('transactionStock');
        if (isset($options['filter']['!fieldstk'])) {
            foreach ($options['filter']['!fieldstk'] as $field) {
                $subForm->remove($field);
            }
        }

        // Suppression des champs du formulaire principal
        if (isset($options['filter']['!fields'])) {
            foreach ($options['filter']['!fields'] as $field) {
                $builder->remove($field);
            }
        }
    }
}
