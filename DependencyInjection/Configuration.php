<?php


namespace CJW\CJWConfigProcessor\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder("cjw_config_processor");
        $rootNode = $treeBuilder->root("cjw_config_processor");

        $rootNode
            ->children()
                ->arrayNode("custom_site_access_parameters")
                    ->children()
                        ->booleanNode("allow")
                            ->defaultFalse()
                        ->end()
                        ->booleanNode("scan_parameters")
                            ->defaultFalse()
                        ->end()
                        ->arrayNode("parameters")
                            ->useAttributeAsKey("name")
                            ->info("The keys which describes what parameters will be added to the site access-view.")
                            ->example(["ezdesign", "cjw.fake.multi_part.parameter"])
                            ->requiresAtLeastOneElement()
                            ->prototype("scalar")->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode("favourite_parameters")
                    ->info("Handles all things regarding favourite parameters.")
                    ->children()
                        ->booleanNode("allow")
                            ->info("Are favourite parameters allowed or shouldn't there be any mechanisms for it.")
                            ->defaultFalse()
                        ->end()
                        ->booleanNode("display_everywhere")
                            ->info("Should the favourites be displayed outside of the dedicated view too or not.")
                            ->defaultFalse()
                        ->end()
                        ->booleanNode("scan_parameters")
                            ->info("Are the parameters supposed to be scanned for and edited for site access dependency or not.")
                            ->defaultFalse()
                        ->end()
                        ->arrayNode("parameters")
                            ->useAttributeAsKey("name")
                            ->info("Keys which describe which parameters are going to be marked as favourites.")
                            ->example(["cjw.fake.multi_part.parameter", "another.parameter.test"])
                            ->requiresAtLeastOneElement()
                            ->prototype("scalar")->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
