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
        $home = new MenuItemModel('home', [
            'label' => 'Tableau de bord',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'home',
        ]);
        $event->addItem($home);

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
                    'label' => sprintf('<img src="%s" height="28"> &nbsp; %s', $account->getInstitution()->getLogo(), $account->getName()),
                    // 'label' => $account->getName(),
                    'route' => sprintf('account_%s_index', $account->getType()->getTypeCode()),
                    'routeArgs' => ['id' => $account->getId()],
                    'icon' => ' ',
                ]));
            }
            $event->addItem($menu);
        }

        $report = new MenuItemModel('report', [
            'label' => 'Rapports',
            'icon' => 'fas fa-chart-bar',
        ]);
        $report->addChild(new MenuItemModel('report_capital', [
            'label' => 'Capitalisation',
            'route' => 'report_capital',
            'icon' => 'fas fa-wallet',
        ]));
        $report->addChild(new MenuItemModel('report_capacity', [
            'label' => 'Capacité d\'épargne',
            'route' => 'report_capacity',
            'icon' => 'fas fa-piggy-bank',
        ]));
        $report->addChild(new MenuItemModel('report_vehicle', [
            'label' => 'Coût des véhicules',
            'route' => 'report_vehicle__index',
            'icon' => 'fas fa-car',
        ]));

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
        $manage->addChild(new MenuItemModel('manage_project', [
            'label' => 'Projets',
            'route' => 'manage_project__index',
            'icon' => 'fas fa-project-diagram',
        ]));
        $manage->addChild(new MenuItemModel('manage_vehicle', [
            'label' => 'Véhicules',
            'route' => 'manage_vehicle__index',
            'icon' => 'fas fa-car',
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
        $manage->addChild(new MenuItemModel('manage_stock', [
            'label' => 'Cotations &amp; Indices',
            'route' => 'manage_stock__index',
            'icon' => 'fas fa-landmark',
        ]));
        $manage->addChild(new MenuItemModel('manage_model', [
            'label' => 'Modèles / planification',
            'route' => 'manage_model__index',
            'icon' => 'far fa-calendar-alt',
        ]));
        $manage->addChild(new MenuItemModel('manage_config', [
            'label' => 'Configuration',
            'route' => 'manage_config__index',
            'icon' => 'fas fa-cogs',
        ]));

        $event
            ->addItem($report)
            ->addItem($manage)
        ;
    }
}
