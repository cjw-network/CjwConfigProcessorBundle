<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;


/**
 * Class CustomGlobLoader is a modified GlobLoader which keeps track of the resources being loaded from a given
 * GlobPattern and relays the information to other classes involved in the LoadingProcess in order to keep track
 * of the loaded resources.
 *
 * @package CJW\CJWConfigProcessor\LocationAwareConfigLoadBundle\src
 */
class CustomGlobLoader extends GlobFileLoader
{

    /**
     * CustomGlobLoader constructor. Its only difference is, that instead of taking a "normal" ContainerBuilder or -Interface,
     * this constructor takes a CustomContainerBuilder in order to ensure compatibility with the rest of the location-aware
     * loading process.
     *
     * @param CustomContainerBuilder $container A container builder which was specifically made to support a loading process that keeps track of the loaded resources.
     * @param FileLocatorInterface $locator A typical FileLocator which is supposed to help locate resources.
     */
    public function __construct(CustomContainerBuilder $container, FileLocatorInterface $locator)
    {
        parent::__construct($container, $locator);
    }

    /**
     * @override
     * This override is basically a copy of the {@see GlobLoader} load function just with one key difference:
     * It tracks the paths gathered by GlobResources and always relays that path before the loading process
     * of the parameters and services begins.
     * 
     * @param $resource
     * @param string|null $type
     *
     * @throws FileLoaderImportCircularReferenceException
     * @throws LoaderLoadException
     */
    public function load($resource, $type = null)
    {
        // Typical load function of the GlobLoader as of Symfony 5.1.5
        foreach ($this->glob($resource, false, $globResource) as $path => $info) {
            // Relay the path to the CustomContainerBuilder
            $this->container->setCurrentLocation($path);
            // continue with the standard loading procedure
            $this->import($path);
        }

        $this->container->addResource($globResource);
    }
}
