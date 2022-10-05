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

        $event
            ->addItem($manage)
        ;
    }
}
