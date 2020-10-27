<?php


namespace App\CJW\LocationAwareConfigLoadBundle\src;


use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Contracts\Cache\ItemInterface;

class LocationRetrievalCoordinator
{

    /** @var LoadInitializer A custom kernel that initiates the entire custom loading process. */
    private static $customConfigLoader;

    /** @var array An array which not only stores the parameters, but also the paths they have been read from (including the values set there) */
    public static $parametersAndLocations;

    /** @var PhpFilesAdapter A cache which is supposed to store the parameters that have been parsed. */
    private static $cache;

    /** @var bool */
    private static $initialized = false;

    public static function initializeCoordinator(): void {
        if (!self::$customConfigLoader) {
            self::$customConfigLoader = new LoadInitializer($_SERVER["APP_ENV"], (bool)$_SERVER["APP_DEBUG"]);
        }

        $cacheDir = self::$customConfigLoader->getCacheDir()."/cjw/config-processor-bundle/";

        if (!self::$cache) {
            try {
                self::$cache = new PhpFilesAdapter("", 0, $cacheDir);
            } catch (CacheException $e) {
                self::$cache = new PhpFilesAdapter();
            }
        }

        if (!self::$parametersAndLocations) {
            // After the booting process of the LoadInitializer, the parameters should be present
            self::$parametersAndLocations = CustomValueStorage::getParametersAndTheirLocations();
        }

        try {
            // TODO Revamp this, maybe?:
            // If parameters are returned (meaning that the kernel has booted and thus new parameters could have entered), delete the parameters present
            if (is_array(self::$parametersAndLocations) && count(self::$parametersAndLocations) > 0) {
                self::$cache->delete("parametersAndLocations");
                self::$cache->delete("cjw_processed_param_objects");
                self::$cache->delete("cjw_processed_params");
            }

            // Then store the presumably "new" parameters
            self::$parametersAndLocations = self::$cache->get("parametersAndLocations", function (ItemInterface $item) {
                $item->set(self::$parametersAndLocations);

                return self::$parametersAndLocations;
            });
        }catch (InvalidArgumentException $e) {
        }

        self::$initialized = true;
    }

    /**
     * @return array
     */
    public static function getParametersAndLocations(): array
    {
        if (!self::$initialized) {
            self::initializeCoordinator();
        }

        return self::$parametersAndLocations;
    }

    public static function getParameterLocations (string $parameterName) {
        if (!self::$initialized) {
            self::initializeCoordinator();
        }

        return self::getLocationsForSpecificParameter($parameterName);
    }

    /**
     * Returns the internal array which keeps track of all encountered locations without any connection to
     * the parameters, values or other information. It resembles a plain "stack" of locations.
     *
     * @param string $parameterName
     * @return array Returns an array which is filled with all encountered locations during the configuration-loading-process.
     */
    private static function getLocationsForSpecificParameter (string $parameterName) {
        $parameterKeySegments = explode(".", $parameterName);

        if (is_array($parameterKeySegments) && count($parameterKeySegments) > 1) {
            if (!isset(self::$parametersAndLocations[$parameterName]) && ($parameterKeySegments[1] !== "default" || $parameterKeySegments[1] !== "global")) {
                $parameterKeySegments[1] = "global";
                $newTryParameterName = join(".",$parameterKeySegments);

                if (!isset(self::$parametersAndLocations[$newTryParameterName])) {
                    $parameterKeySegments[1] = "default";
                    $newTryParameterName = join(".",$parameterKeySegments);
                }

                return isset(self::$parametersAndLocations[$newTryParameterName])? self::$parametersAndLocations[$newTryParameterName] : null;
            }
        }

        // Only if that parameter exists as a key in the array, will that parameters paths and values be returned, otherwise null
        return isset(self::$parametersAndLocations[$parameterName])? self::$parametersAndLocations[$parameterName] : null;
    }
}
