<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Datatable;

use App\Entity\Recipient;
use Olix\BackOfficeBundle\Datatable\AbstractDatatable;
use Olix\BackOfficeBundle\Datatable\Column\ActionColumn;
use Olix\BackOfficeBundle\Datatable\Column\Column;

/**
 * Classe RecipientDatable.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RecipientDatatable extends AbstractDatatable
{
    public function getLineFormatter()
    {
        $formatter = function ($row) {
            if (null !== $row['category']) {
                $row['category']['name'] = $row['category']['parent']['name'].' : '.$row['category']['name'];
            }

            return $row;
        };

        return $formatter;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<mixed> $options
     */
    public function buildDatatable(array $options = []): void
    {
        $this->ajax->set([]);

        $this->options->set([
            'individual_filtering' => false,
            'order' => [[0, 'asc']],
        ]);

        $this->columnBuilder
            ->add('name', Column::class, [
                'title' => 'Nom',
                'searchable' => true,
            ])
            ->add('category.name', Column::class, [
                'title' => 'Catégorie',
                'default_content' => '',
            ])
            ->add('category.parent.name', Column::class, [
                'title' => 'Catégorie',
                'visible' => false,
                'default_content' => '',
            ])
            ->add(null, ActionColumn::class, [
                'actions' => [
                    [
                        'route' => 'manage_recipient__edit',
                        'icon' => 'fas fa-edit',
                        'label' => 'Modifier',
                        'route_parameters' => [
                            'id' => 'id',
                        ],
                        'attributes' => [
                            'rel' => 'tooltip',
                            'title' => 'Modifier ce bénéficiaire',
                            'class' => 'btn btn-primary btn-sm',
                            'data-toggle' => 'olix-modal',
                            'data-target' => '#modalForm',
                        ],
                    ],
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity(): string
    {
        return Recipient::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'recipient_datatable';
    }
}
