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
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;

/**
 * Datatable des bénéficiaires.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class RecipientTableType implements DataTableTypeInterface
{
    /**
     * @param DataTable    $dataTable
     * @param array<mixed> $options
     */
    public function configure(DataTable $dataTable, array $options): void
    {
        $dataTable
            ->add('name', TextColumn::class, [
                'label' => 'Nom',
                'searchable' => true,
            ])
            ->add('category', TextColumn::class, [
                'label' => 'Catégorie',
                'data' => fn ($row) => sprintf('%s', $row->getCategory()),
            ])
            ->add('buttons', TwigColumn::class, [
                'label' => '',
                'className' => 'text-right align-middle',
                'template' => 'manage/recipient-buttonbar.html.twig',
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Recipient::class,
                'query' => function (QueryBuilder $builder) {
                    return $builder
                        ->select('r')
                        ->addSelect('c')
                        ->addSelect('p')
                        ->from(Recipient::class, 'r')
                        ->leftJoin('r.category', 'c')
                        ->leftJoin('c.parent', 'p')
                    ;
                },
            ])
        ;
    }
}
