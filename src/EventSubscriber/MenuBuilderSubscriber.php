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
use Olix\BackOfficeBundle\Model\MenuItemInterface;
use Olix\BackOfficeBundle\Model\MenuItemModel;

/**
 * Subscriber du menu.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
final class MenuBuilderSubscriber extends MenuFactorySubscriber
{
    public function build(SidebarMenuEvent $event): void
    {
        $home = new MenuItemModel('home', [
            'label' => 'Tableau de bord',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'home',
        ]);
        $event->addMenuItem($home);

        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accountsByType = $repository->findGroupByTypeOpened();
        foreach ($accountsByType as $key => $type) {
            $menu = $this->getMenuAccountsByType($key, $type);
            if (!$menu instanceof MenuItemInterface) {
                continue;
            }
            $event->addMenuItem($menu);
        }

        $event
            ->addMenuItem($this->getMenuReport())
            ->addMenuItem($this->getMenuManager())
        ;
    }

    /**
     * Retourne les menu de chaque compte pour un groupe de type donné.
     *
     * @param array<mixed> $typeAccount
     */
    private function getMenuAccountsByType(int $typeId, array $typeAccount): ?MenuItemInterface
    {
        if (0 === count($typeAccount['accounts'])) {
            return null;
        }
        $menu = new MenuItemModel('type_'.$typeId, [
            'label' => $typeAccount['menu'],
            'icon' => $typeAccount['icon'],
        ]);
        foreach ($typeAccount['accounts'] as $account) {
            /** @var Account $account */
            $menu->addChild(new MenuItemModel('account'.$account->getId(), [
                'label' => sprintf('<img src="%s" height="28"> &nbsp; %s', $account->getInstitution()->getLogo(), $account->getName()),
                // 'label' => $account->getName(),
                'route' => sprintf('account_%s_index', $account->getType()->getTypeCode()),
                'routeArgs' => ['id' => $account->getId()],
                'icon' => ' ',
            ]));
        }

        return $menu;
    }

    /**
     * Retourne le menu des rapports.
     */
    private function getMenuReport(): MenuItemInterface
    {
        $menu = new MenuItemModel('report', [
            'label' => 'Rapports',
            'icon' => 'fas fa-chart-bar',
        ]);
        $menu->addChild(new MenuItemModel('report_capital', [
            'label' => 'Capitalisation',
            'route' => 'report_capital',
            'icon' => 'fas fa-wallet',
        ]));
        $menu->addChild(new MenuItemModel('report_capacity', [
            'label' => 'Capacité d\'épargne',
            'route' => 'report_capacity',
            'icon' => 'fas fa-piggy-bank',
        ]));
        $menu->addChild(new MenuItemModel('report_vehicle', [
            'label' => 'Coût des véhicules',
            'route' => 'report_vehicle__index',
            'icon' => 'fas fa-car',
        ]));
        $menu->addChild(new MenuItemModel('report_project', [
            'label' => 'Projets',
            'route' => 'report_project__index',
            'icon' => 'fas fa-project-diagram',
        ]));

        return $menu;
    }

    /**
     * Retourne le menu de la gestion de l"application.
     */
    private function getMenuManager(): MenuItemInterface
    {
        $menu = new MenuItemModel('manage', [
            'label' => 'Gerer ses finances',
            'icon' => 'fas fa-cogs',
        ]);
        $menu->addChild(new MenuItemModel('manage_org', [
            'label' => 'Organismes',
            'route' => 'manage_institution__index',
            'icon' => 'far fa-building',
        ]));
        $menu->addChild(new MenuItemModel('manage_account', [
            'label' => 'Comptes / Contrats',
            'route' => 'manage_account__index',
            'icon' => 'fas fa-piggy-bank',
        ]));
        $menu->addChild(new MenuItemModel('manage_recipient', [
            'label' => 'Bénéficiaires',
            'route' => 'manage_recipient__index',
            'icon' => 'fas fa-users',
        ]));
        $menu->addChild(new MenuItemModel('manage_category', [
            'label' => 'Catégories',
            'route' => 'manage_category__index',
            'icon' => 'fas fa-layer-group',
        ]));
        $menu->addChild(new MenuItemModel('manage_stock', [
            'label' => 'Cotations &amp; Indices',
            'route' => 'manage_stock__index',
            'icon' => 'fas fa-landmark',
        ]));
        $menu->addChild(new MenuItemModel('manage_model', [
            'label' => 'Modèles / planification',
            'route' => 'manage_model__index',
            'icon' => 'far fa-calendar-alt',
        ]));
        $menu->addChild(new MenuItemModel('manage_config', [
            'label' => 'Configuration',
            'route' => 'manage_config__index',
            'icon' => 'fas fa-cogs',
        ]));

        return $menu;
    }
}
