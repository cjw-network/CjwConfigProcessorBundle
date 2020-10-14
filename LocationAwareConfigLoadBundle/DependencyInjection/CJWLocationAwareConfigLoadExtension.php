<?php


namespace App\CJW\LocationAwareConfigLoadBundle\DependencyInjection;


use App\CJW\LocationAwareConfigLoadBundle\src\ConfigPathUtility;
use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser;

class CJWLocationAwareConfigLoadExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader( $container, new FileLocator(__DIR__ . '/../Resources/config') );
        try {
            $loader->load('services.yaml');
        } catch (Exception $e) {
            print(`An error occured while trying to process the "services.yml". ${$e->getMessage()}`);
        }
    }
}
