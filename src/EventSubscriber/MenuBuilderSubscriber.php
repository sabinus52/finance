<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use Olix\BackOfficeBundle\Event\SidebarMenuEvent;
use Olix\BackOfficeBundle\EventSubscriber\MenuFactorySubscriber;
use Olix\BackOfficeBundle\Model\MenuItemModel;

class MenuBuilderSubscriber extends MenuFactorySubscriber
{
    public function build(SidebarMenuEvent $event): void
    {
        $manage = new MenuItemModel('manage', [
            'label' => 'Gerer ses finances',
            'icon' => 'fas fa-cogs',
        ]);
        $manage->addChild(new MenuItemModel('manage_org', [
            'label' => 'Organismes',
            'route' => 'manage_institution__index',
            'icon' => 'far fa-building',
        ]));
        $manage->addChild(new MenuItemModel('manage_account', [
            'label' => 'Comptes / Contrats',
            'route' => 'manage_account__index',
            'icon' => 'fas fa-piggy-bank',
        ]));
        $manage->addChild(new MenuItemModel('manage_recipient', [
            'label' => 'Bénéficiaires',
            'route' => 'manage_recipient__index',
            'icon' => 'fas fa-users',
        ]));
        $manage->addChild(new MenuItemModel('manage_category', [
            'label' => 'Catégories',
            'route' => 'manage_category__index',
            'icon' => 'fas fa-layer-group',
        ]));

        $event
            ->addItem($manage)
        ;
    }
}
