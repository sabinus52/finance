<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Olix\BackOfficeBundle\Event\SidebarMenuEvent;
use Olix\BackOfficeBundle\EventSubscriber\MenuFactorySubscriber;
use Olix\BackOfficeBundle\Model\MenuItemModel;

class MenuBuilderSubscriber extends MenuFactorySubscriber
{
    public function build(SidebarMenuEvent $event): void
    {
        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accountsByType = $repository->findGroupByTypeOpened();
        foreach ($accountsByType as $key => $type) {
            if (0 === count($type['accounts'])) {
                continue;
            }
            $menu = new MenuItemModel('type_'.$key, [
                'label' => $type['menu'],
                'icon' => $type['icon'],
            ]);
            foreach ($type['accounts'] as $account) {
                /** @var Account $account */
                $menu->addChild(new MenuItemModel('account'.$account->getId(), [
                    'label' => $account->getInstitution()->getName().' '.$account->getName(),
                    'route' => 'account__index',
                    'routeArgs' => ['id' => $account->getId()],
                    'icon' => ' ',
                ]));
            }
            $event->addItem($menu);
        }

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
