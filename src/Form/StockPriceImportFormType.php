<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire dd'importation d'un fichier de cotations.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class StockPriceImportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Fichier CSV',
                'constraints' => [
                    new File([
                        'extensions' => ['csv'],
                        'extensionsMessage' => 'Merci de sélectionner un fichier CSV',
                    ]),
                ],
            ])
            ->add('header', CheckboxType::class, [
                'label' => 'Si présence d\'une entête',
                'required' => false,
            ])
            ->add('date', NumberType::class, [
                'label' => 'Colonne "date"',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Colonne "valeur"',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }
}
