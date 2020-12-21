<?php

namespace CJW\CJWConfigProcessor\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigProcessingMenuSubscriber is responsible for adding the config processing view as a tab under the
 * eZ / Ibexa Platform Backoffice main admin tab list.
 *
 * @package CJW\CJWConfigProcessor\EventSubscriber
 */
class ConfigProcessingMenuSubscriber implements EventSubscriberInterface {

    /**
     * @override
     * Through this function it is possible to get the main menu and perform an action as the menu is being built / as it
     * has finished building.
     *
     * @return array|array[] Returns an array that states on what event the the menu functionality should be triggered.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ["onMenuConfigure",0],
        ];
    }

    /**
     * @override
     * This function is being called as soon as the ConfigureMenuEvent regarding the Main Menu has fired / the
     * Subscriber has noticed it firing.
     *
     * @param ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event) {
        $menu = $event->getMenu();

        if (!isset($menu[MainMenuBuilder::ITEM_ADMIN])) {
            return;
        }

        $menu[MainMenuBuilder::ITEM_ADMIN]->addChild(
            "cjw_config_processing",
            [
                "label" => "Config Processing View",
                "route" => "cjw_config_processing.site_access_param_list",
                "extras" => ["icon" => "list"],
            ]
        );
    }
}
