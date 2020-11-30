<?php


namespace App\CJWConfigProcessorBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;


class CJWConfigProcessorExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader( $container, new FileLocator(__DIR__ . '/../Resources/config') );
        $loader->load('services.yml');
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration,$configs);

        $this->handleCustomParamConfig($config, $container);
        $this->handleFavouriteParamConfig($config, $container);
    }

    private function handleCustomParamConfig (array $config, ContainerBuilder $container) {
        if (!isset($config["custom_site_access_parameters"])) {
            $allowParameters = false;
            $scanParameters = false;
        } else {
            $allowParameters = $config["custom_site_access_parameters"]["allow"];
            $scanParameters = $config["custom_site_access_parameters"]["scan_parameters"];
            $container->setParameter("cjw.custom_site_access_parameters.parameters",$config["custom_site_access_parameters"]["parameters"]);
        }

        $container->setParameter("cjw.custom_site_access_parameters.active", $allowParameters);
        $container->setParameter("cjw.custom_site_access_parameters.scan_parameters", $scanParameters);
    }

    private function handleFavouriteParamConfig (array $config, ContainerBuilder $container) {
        if (!isset($config["favourite_parameters"])) {
            $allowParameters = false;
            $scanParameters = false;
            $displayEverywhere = false;
        } else {
            $allowParameters = $config["favourite_parameters"]["allow"];
            $scanParameters = $config["favourite_parameters"]["scan_parameters"];
            $displayEverywhere = $config["favourite_parameters"]["display_everywhere"];
            $container->setParameter("cjw.favourite_parameters.parameters", $config["favourite_parameters"]["parameters"]);
        }

        $container->setParameter("cjw.favourite_parameters.allow", $allowParameters);
        $container->setParameter("cjw.favourite_parameters.display_everywhere", $displayEverywhere);
        $container->setParameter("cjw.favourite_parameters.scan_parameters", $scanParameters);
    }
}
