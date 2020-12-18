<?php


namespace CJW\CJWConfigProcessor\EventSubscriber;


use EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RightSideBarSiteAccessComparisonMenuBuilder is responsible for building the right sidebar menu for the
 * bundle frontend.
 *
 * @package CJW\CJWConfigProcessor\EventSubscriber
 */
class RightSideBarSiteAccessComparisonMenuBuilder extends AbstractBuilder
{

    const ITEM__SINGLE_SITEACCESS_VIEW = 'Single Site Access View';
    const ITEM__NORMAL_COMPARISON_VIEW = 'Default Comparison';
    const ITEM__COMMON_PARAMETERS_VIEW = 'Commons Comparison';
    const ITEM__UNCOMMON_PARAMETERS_VIEW = 'Differences Comparison';
    const ITEM__HIGHLIGHT_DIFFERENCES = 'Highlight Differences';
    const ITEM__SYNCHRONOUS_SCROLLING = 'Synchronous Scrolling';

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
                    self::ITEM__SINGLE_SITEACCESS_VIEW,
                    [
                        'extras' => ['icon' => 'view', 'orderNumber' => 58],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                            "cjw_id" => "cjw_single_sa_view",
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__NORMAL_COMPARISON_VIEW,
                    [
                        'extras' => ['icon' => 'back', 'orderNumber' => 59],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                            "cjw_id" => "cjw_show_normal_comparison",
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__COMMON_PARAMETERS_VIEW,
                    [
                        'extras' => ['icon' => 'table-column', 'orderNumber' => 60],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                            "cjw_id" => "cjw_show_common_parameters",
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__UNCOMMON_PARAMETERS_VIEW,
                    [
                        'extras' => ['icon' => 'table-cell', 'orderNumber' => 61],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'change view',
                            "cjw_id" => "cjw_show_uncommon_parameters",
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
                            "cjw_id" => "cjw_highlight_differences",
                        ],
                    ]
                ),
                $this->createMenuItem(
                    self::ITEM__SYNCHRONOUS_SCROLLING,
                    [
                        'extras' => ['icon' => 'link', 'orderNumber' => 63],
                        'attributes' => [
                            'class' => 'ez-btn--reveal',
                            'data-actions' => 'highlight',
                            "cjw_id" => "cjw_synchronous_scrolling",
                        ],
                    ]
                ),
            ]
        );

        return $menu;
    }
}
