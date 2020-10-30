<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RightSideBarSiteAccessSelectionMenuBuilder extends AbstractBuilder
{
    const ITEM__SPLIT_VIEW = 'Comparison View';

    public function __construct(MenuItemFactory $factory, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($factory, $eventDispatcher);
    }

    protected function getConfigureEventName(): string
    {
        return ConfigureMenuEvent::CONTENT_SIDEBAR_RIGHT;
    }

    protected function createStructure(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->setChildren(
            [
                $this->createMenuItem(
                    self::ITEM__SPLIT_VIEW,
                    [
                        'extras' => ['icon' => 'reveal', 'orderNumber' => 60],
                        'attributes' => [
                            'cjw_id' => 'cjw_split_selection',
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'enable selection',
                        ],
                    ]
                ),
            ]
        );

        return $menu;
    }
}
