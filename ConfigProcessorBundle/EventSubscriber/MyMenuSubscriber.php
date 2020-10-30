<?php

namespace App\CJW\ConfigProcessorBundle\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * If the autoconfigure option is set to false in the service.yaml, then this Menu-Subscriber would have to be registered separately
 * in the yaml as: (under services) (Path to my class): App\EventListener\<My Class Name I Gave>: then tags: then - { name: kernel.event.subscriber }
 */
class MyMenuSubscriber implements EventSubscriberInterface {

    /**
     * Through this function it is possible for me to get the main menu and perform an action as the menu is being build / as it
     * has finished building. Thus I am able to inject other menu items.
     * @return array|array[]
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ["onMenuConfigure",0],
        ];
    }

    /**
     * This function is being called as soon as the ConfigureMenuEvent regarding the Main Menu has fired / the
     * Subscriber has noticed it firing. In this function I am able to not only get the main menu as an object but also to
     * influence and actively change the menu.
     * @param ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event) {
        $menu = $event->getMenu();

//        $menu->addChild(
//            "menu_item1",
//            [
//                "label" => "CJW-Bundle-Functions", "extras" => ["icon" => "file"]
//            ]
//        );

//        $menu["menu_item1"]->addChild(
//            "2nd_level_menu_item",
//            [
//                "label" => "Config Processing View",
//                "uri" => "/admin/cjw/config-processing",
//                "extras" => ["icon" => "article"],
//            ]
//        );

        if (!isset($menu[MainMenuBuilder::ITEM_ADMIN])) {
            return;
        }

        $menu[MainMenuBuilder::ITEM_ADMIN]->addChild(
            "cjw_config_processing",
            [
                "label" => "Config Processing View",
                "route" => "cjw_config_processing.index",
                "extras" => ["icon" => "article"],
            ]
        );

//        $menu["menu_item1"]->addChild(
//            "template_test_menu_item",
//            [
//                "label" => "Template Test Item",
//                "extras" => [
//                    "template" => "admin_ui/menu_item_template.html.twig",
//                    "template_parameters" => [
//                        "custom_parameter" => "value",
//                    ],
//                ],
//            ],
//        );
    }
}
