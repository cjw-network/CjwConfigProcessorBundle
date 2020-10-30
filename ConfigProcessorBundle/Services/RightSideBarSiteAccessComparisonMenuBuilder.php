<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RightSideBarSiteAccessComparisonMenuBuilder extends \EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder
{

    const ITEM__COMMON_PARAMETERS_VIEW = 'Common Parameters Only';
    const ITEM__UNCOMMON_PARAMETERS_VIEW = 'Uncommon Parameters Only';
    const ITEM__HIGHLIGHT_DIFFERENCES = 'Highlight Differences';

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
                    self::ITEM__COMMON_PARAMETERS_VIEW,
                    [
                        'extras' => ['icon' => 'browse', 'orderNumber' => 60],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__UNCOMMON_PARAMETERS_VIEW,
                    [
                        'extras' => ['icon' => 'browse', 'orderNumber' => 61],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__HIGHLIGHT_DIFFERENCES,
                    [
                        'extras' => ['icon' => 'copy-subtree', 'orderNumber' => 62],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'highlight',
                        ],
                    ]
                ),
            ]
        );

        return $menu;
    }
}
