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
                ->info("For configuring the custom parameters to be added to the site access view")
                    ->children()
                        ->booleanNode("allow")
                            ->info("Describes whether custom parameters are allowed and active in the bundle or not.")
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
                            ->info("Configures whether favourite parameters are allowed at all.")
                            ->defaultFalse()
                        ->end()
                        ->booleanNode("display_everywhere")
                            ->info("Describes whether the favourites should be displayed outside of their dedicated view, currently of no use.")
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
                ->arrayNode("env_variables")
                ->info("For configuring the environment variable display")
                    ->children()
                        ->booleanNode("allow")
                            ->info("Determines whether the feature is enabled in the bundle or not.")
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
