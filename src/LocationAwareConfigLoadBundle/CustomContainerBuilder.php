<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use Exception;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class CustomContainerBuilder is a ContainerBuilder which is mainly responsible for keeping track of the various paths
 * being loaded during the process and inserting the custom parameterbag, so that the various origins can be tracked. Its
 * biggest role is to inhibit any additions to itself during load process of the found (custom) bundle config routes, so
 * that the process does not interfere with standard loading  and cause errors.
 *
 * @package CJW\CJWConfigProcessor\LocationAwareConfigLoadBundle\src
 */
class CustomContainerBuilder extends ContainerBuilder
{
    /**
     * @var bool Boolean which determines whether the bundle configuration mode is active or not.
     */
    private $isBundleConfigMode;

    public function __construct()
    {
        // Provide the container with a custom ParameterBag in order to allow location awareness.
        parent::__construct(new LocationAwareParameterBag());
        $this->isBundleConfigMode = false;
    }

    /**
     * Sets the current location to the location string given to the function.
     *
     * @param string $location The location to be set.
     */
    public function setCurrentLocation(string $location)
    {
        /** The parameterBag is the custom one created to feature such a function */
        $this->parameterBag->setCurrentLocation($location);
    }

    /**
     * @override
     * In order to be able to actively influence the way locations are read for parameters during the bundle configuration
     * process, the compilation of the container is caught through this function and then, after the measures for the
     * bundle configuration are set in place, the normal compilation process of the container takes place.
     *
     * <br>This was done in order to prevent the bundles from constantly adding the same parameters unchanged back into
     * the container, which led to dozens of useless entries into the location lists for every parameter.
     *
     * @param bool $resolveEnvPlaceholders
     */
    public function compile(bool $resolveEnvPlaceholders = false)
    {
        // prior to the compilation starting, set the current location to a blanket "bundles", so that if no other paths can be found, it is at least
        // known, that the parameters come from the bundle process.
        $this->setCurrentLocation("Bundles");
        CustomValueStorage::activateBundleConfigMode(true);
        try {
            parent::compile($resolveEnvPlaceholders);
        } catch (Exception $error) {
        }
    }

    /**
     * {@override}
     * This is an extension of the function. Its purpose is to not only track the bundle being processed, but to determine
     * its residence in the file system and provide that information to the appropriate classes, in order to allow the bundle's
     * config directories to be tracked.
     *
     * @param string $name The name of the bundle who's extension config to retrieve.
     *
     * @return array Returns the found configuration.
     */
    public function getExtensionConfig(string $name): array
    {
        try {
            // Get the class of the extension, then generate a Reflection class out of that, which allows finding the path to the file, then set that path
            $extensionClass = get_class($this->getExtension($name));
            $extensionReflector = new ReflectionClass($extensionClass);
            $extensionLocation = $extensionReflector->getFileName();
            $this->setCurrentLocation($extensionLocation);

            // Following the Symfony-Bundle-Conventions, the config directory of the bundles is being set instead of the extension class
            $extensionLocation = ConfigPathUtility::convertExtensionPathToConfigDirectory($extensionLocation);
            if ($extensionLocation && is_string($extensionLocation)) {
                ConfigPathUtility::addPathToPathlist($extensionLocation);
            }
        } catch (Exception $error) {
            // In the event that something fails in the above procedure to determine the correct paths, just take the name of the extension as a path
            $this->setCurrentLocation($name);
        }

        // continue the typical, normal extension-config-functionality
        return parent::getExtensionConfig($name);
    }

    /**
     * @override
     * This override ensures, that no definition of a service will be added while loading the config files of the bundles
     * outside of the bundle configuration phase.
     *
     * @param array $definitions
     */
    public function addDefinitions(array $definitions): void
    {
        if (!$this->isBundleConfigMode) {
            parent::addDefinitions($definitions);
        }
    }

    /**
     * @override
     * This override ensures, that no service will be registered while loading the config files of the bundles
     * outside of the bundle configuration phase.
     *
     * @param string $id
     * @param string|null $class
     *
     * @return Definition|null
     */
    public function register(string $id, string $class = null): ?Definition
    {
        if (!$this->isBundleConfigMode) {
            return parent::register($id, $class);
        }

        return null;
    }

    /**
     * @override
     * This override ensures, that no service definition will be added while loading the config files of the bundles
     * outside of the bundle configuration phase.
     *
     * @param string $id
     * @param Definition $definition
     *
     * @return Definition|null
     */
    public function setDefinition(string $id, Definition $definition): ?Definition
    {
        if (!$this->isBundleConfigMode) {
            return parent::setDefinition($id, $definition);
        }

        return null;
    }

    /**
     * @override
     * This override ensures, that no service alias will be set while loading the config files of the bundles
     * outside of the bundle configuration phase.
     *
     * @param string $alias
     * @param $id
     *
     * @return string|Alias|null
     */
    public function setAlias(string $alias, $id)
    {
        if (!$this->isBundleConfigMode) {
            return parent::setAlias($alias, $id);
        }

        return null;
    }

    /**
     * @override
     * This override ensures, that no service definition will be registered while loading the config files of the bundles
     * outside of the bundle configuration phase.
     *
     * @param array $definitions
     */
    public function setDefinitions(array $definitions)
    {
        if (!$this->isBundleConfigMode) {
            parent::setDefinitions($definitions);
        }
    }

    /**
     * Dictates whether bundle config paths are being loaded outside of the bundle configuration part of the
     * config load process (namely the {@see MergeExtensionConfigurationPass}).
     *
     * @param bool $isBundleConfigMode A boolean which sets the bundleConfigMode either to true or false
     *                                 (if set to true, no services will be registered to the container).
     */
    public function setIsBundleConfigMode(bool $isBundleConfigMode): void
    {
        $this->isBundleConfigMode = $isBundleConfigMode;
    }
}
