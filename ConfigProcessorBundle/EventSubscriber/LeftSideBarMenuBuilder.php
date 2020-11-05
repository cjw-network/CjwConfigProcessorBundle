<?php


namespace App\CJW\ConfigProcessorBundle\EventSubscriber;


use EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeftSideBarMenuBuilder extends AbstractBuilder implements TranslationContainerInterface
{

    /* Menu items */
    const ITEM__PARAMETERLIST = 'Parameter List';
    const ITEM__PARAMETER_LIST_SITE_ACCESS = 'Parameter List: Site Access';

    public function __construct(
        MenuItemFactory $factory,
        EventDispatcherInterface $eventDispatcher
    )
    {
        parent::__construct($factory, $eventDispatcher);
    }

    protected function getConfigureEventName(): string
    {
        return ConfigureMenuEvent::CONTENT_SIDEBAR_LEFT;
    }

    protected function createStructure(array $options): ItemInterface
    {
        $menu = $this->factory->createItem("root");

        $menuItems = [
            self::ITEM__PARAMETER_LIST_SITE_ACCESS => $this->createMenuItem(
                self::ITEM__PARAMETER_LIST_SITE_ACCESS,
                [
                    "route" => "cjw_config_processing.site_access_param_list",
                    "extras" => ["icon" => "view-list"],
                ]
            ),
            self::ITEM__PARAMETERLIST => $this->createMenuItem(
                self::ITEM__PARAMETERLIST,
                [
                    "route" => "cjw_config_processing.param_list",
                    "extras" => ["icon" => "list"],
                ]
            ),
        ];

        $menu->setChildren($menuItems);

        return $menu;
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM__PARAMETERLIST,"menu"))->setDesc("Parameter List"),
            (new Message(self::ITEM__PARAMETER_LIST_SITE_ACCESS, "menu"))->setDesc("Parameter List Site Access")
        ];
    }
}