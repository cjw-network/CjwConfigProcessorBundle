<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use Exception;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

/**
 * Class CustomDelegatingLoader is a modified DelegatingLoader which delegates not only the loading process but also
 * the path that is currently being loaded to other classes in order to allow a location aware loading process.
 *
 * @package CJW\CJWLocationAwareConfigLoadBundle\src
 */
class CustomDelegatingLoader extends DelegatingLoader
{

    /**
     * @var CustomContainerBuilder A container builder which serves to build the container while keeping track of the files used to do so.
     */
    private $container;

    public function __construct(LoaderResolverInterface $resolver, CustomContainerBuilder $containerBuilder)
    {
        // Though initially constructed as a "normal" DelegatingLoader would, afterwards the internal container is set to the custom ContainerBuilder
        parent::__construct($resolver);

        // This variable is added so that the DelegatingLoader is capable of setting config paths during the config load process as well.
        $this->container = $containerBuilder;
    }

    /**
     * @override
     * This override ensures that everytime a resource is loaded (which is not a global pattern) the path to said resource is set
     * in and known by the container.
     *
     * @param $resource
     * @param string|null $type
     *
     * @throws Exception
     */
    public function load($resource, $type = null)
    {
        // If the given resource is no glob pattern but instead something else and the resource is a string, the path will be given to the container.
        if ($type !== "glob" && !is_object($type) && is_string($resource)) {
            $this->container->setCurrentLocation($resource);
        }

        // Afterwards continue with the standard loading procedure of Symfony.
        return parent::load($resource, $type);
    }
}
