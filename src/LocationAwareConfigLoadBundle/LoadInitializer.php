<?php


namespace App\CJWConfigProcessorBundle\src\LocationAwareConfigLoadBundle;

use App\Kernel;
use Exception;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class LoadInitializer is a custom kernel designed to initiate a location aware configuration process for the Symfony
 * application. That means, that the entire load process will take place, but that every change in the values of the
 * parameters through the configuration will be tracked and their origin stored. This kernel therefore offers a bit
 * of changed functionality compared to the standard kernel in order to allow such a process.
 *
 * @package App\CJWConfigProcessorBundle\LocationAwareConfigLoadBundle\src
 */
class LoadInitializer extends Kernel
{

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        // TODO: Review: Should this entire batch of commands be outsourced to an initializer of the whole thing of some sort? It should not stay in this form in here.
        try {
            // First the extensions of the file formats that are being supported by Symfony with regards to configuration.
            ConfigPathUtility::setConfigExtensions('.{php,xml,yaml,yml}');
            // Get a custom cache directory in order to prevent the paths to be cached only temporarily (leading to problems when restarting the server and the Kernel is still cached, but the routes aren't)
            ConfigPathUtility::setCacheDir($this->getCacheDir());
            // Then initialise the key piece in the tracking of parameter paths.
            ConfigPathUtility::initializePathUtility();
            // Boot the custom kernel and initiate the location aware loading process.
            $this->boot();
            // Save the paths that have been found during the config load process.
            ConfigPathUtility::storePaths();

            // If there have been new paths (which have not yet been loaded separately by the kernel), restart the entire process with these new paths.
            if (ConfigPathUtility::isSupposedToRestart()) {
                $this->cleanUpCache();
                CustomValueStorage::reset();
                $this->reboot(null);
            }
        } catch (Exception $error) {
        }
    }

    /**
     * @override
     * Overrides the standard function of the kernel in order to ensure, that the right CustomContainerBuilder and CustomLoaders
     * are being used for the config loading process.
     * @param ContainerInterface $container
     * @return CustomDelegatingLoader
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this);
        /** @var CustomContainerBuilder $container */
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new CustomGlobLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new CustomDelegatingLoader($resolver, $container);
    }

    /**
     * @override
     * Overrides the standard function of the kernel in order to ensure, that the kernel works with the CustomContainerBuilder.
     */
    protected function getContainerBuilder()
    {
        $originalContainerBuilder = parent::getContainerBuilder();
        $customContainerBuilder = new CustomContainerBuilder();
        $customContainerBuilder->merge($originalContainerBuilder);
        return $customContainerBuilder;
    }

    protected function configureContainer(
        ContainerBuilder $container,
        LoaderInterface $loader
    ): void {
        parent::configureContainer($container, $loader);

        // After the original Symfony-Loading of specific routes, the custom routes, added in the configuration, are being parsed through
        $customPaths = ConfigPathUtility::getConfigPaths();

        $container->setIsBundleConfigMode(true);

        foreach ($customPaths as $path => $isGlob) {
            $type = $isGlob ? "glob" : null;

            try {
                $loader->load($path, $type);
            } catch (Exception $e) {
            }
        }

        $container->setIsBundleConfigMode(false);
    }

    /**
     * This function is supposed to remove the cache-files that have already been created of this kernel
     * during the boot process.
     * <br>
     * It is employed to allow a reboot to occur during the loading process (in order to take newly found config-paths into account).
     */
    private function cleanUpCache() {
        $glob = glob($this->getCacheDir()."/*");

        foreach ($glob as $file) {
            if (preg_match("/^App.*LoadInitializer.*\..+$/",basename($file))) {
                unlink($file);
            }
        }
    }
}
