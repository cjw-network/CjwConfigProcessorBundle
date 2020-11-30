<?php


namespace App\CJW\ConfigProcessorBundle\src\LocationAwareConfigLoadBundle;


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

    /**
     * "Initiates" the class and sets all missing and non-instantiated attributes of the class prior to the rest
     * of its functions being called.
     */
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
            // If parameters are returned (meaning that the kernel has booted and thus new parameters could have entered), delete the parameters present
            // also delete the processed parameters based on the previous parameters
            if (
                is_array(self::$parametersAndLocations) &&
                count(self::$parametersAndLocations) > 0
            ) {
                self::$cache->delete("parametersAndLocations");
                self::$cache->delete("cjw_processed_param_objects");
                self::$cache->delete("cjw_processed_params");
                self::$cache->delete("cjw_processing_timestamp");
            }

            // Then store the presumably "new" parameters
            self::$parametersAndLocations =
                self::$cache->get("parametersAndLocations", function (ItemInterface $item) {
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

    public static function getParameterLocations (
        string $parameterName,
        array $siteAccessGroups = null,
        bool $withSiteAccess = false
    ) {
        if (!self::$initialized) {
            self::initializeCoordinator();
        }

        return self::getLocationsForSpecificParameter($parameterName, $siteAccessGroups, $withSiteAccess);
    }

    /**
     * Returns the internal array which keeps track of all encountered locations without any connection to
     * the parameters, values or other information. It resembles a plain "stack" of locations.
     *
     * @param string $parameterName
     * @param array|null $siteAccessGroups
     * @param bool $withSiteAccess
     * @return array Returns an array which is filled with all encountered locations during the configuration-loading-process.
     */
    private static function getLocationsForSpecificParameter (
        string $parameterName,
        array $siteAccessGroups = null,
        bool $withSiteAccess = false
    ) {
        $parameterKeySegments = explode(".", $parameterName);

        if (is_array($parameterKeySegments) && count($parameterKeySegments) > 1) {
            $results = $resultCarrier = [];
            $siteAccess = "";

                if ($withSiteAccess && $parameterKeySegments[1] !== "default") {
                    $resultCarrier =
                        self::getLocationsFromRewrittenSiteAccessParameter("default",$parameterKeySegments);

                    if (count($resultCarrier) > 0) {
                        $siteAccess = "default";
                    }

                    foreach($resultCarrier as $resultKey => $resultParameter) {
                        $results[$resultKey] = $resultParameter;
                    }
                }

                if ($withSiteAccess && $siteAccessGroups) {
                    foreach ($siteAccessGroups as $singleSiteAccessGroup) {
                        if (
                            $singleSiteAccessGroup !== "default" &&
                            $singleSiteAccessGroup !== $parameterKeySegments[1] &&
                            $singleSiteAccessGroup !== "global"
                        ) {
                            $resultCarrier =
                                self::getLocationsFromRewrittenSiteAccessParameter($singleSiteAccessGroup,$parameterKeySegments);

                            if (count($resultCarrier) > 0) {
                                $siteAccess = $singleSiteAccessGroup;
                            }

                            foreach($resultCarrier as $resultKey => $resultParameter) {
                                $results[$resultKey] = $resultParameter;
                            }
                        }
                    }
                }

                $resultCarrier = (isset(self::$parametersAndLocations[$parameterName])) ?
                    self::$parametersAndLocations[$parameterName] : [];

                if ($withSiteAccess && count($resultCarrier) > 0) {
                    $siteAccess= $parameterKeySegments[1];
                }

                foreach($resultCarrier as $resultKey => $resultValue) {
                    $results[$resultKey] = $resultValue;
                }

                if ($withSiteAccess && $parameterKeySegments[1] !== "global") {
                    $resultCarrier =
                        self::getLocationsFromRewrittenSiteAccessParameter("global",$parameterKeySegments);

                    if (count($resultCarrier) > 0) {
                        $siteAccess= "global";
                    }

                    foreach($resultCarrier as $resultKey => $resultValue) {
                        $results[$resultKey] = $resultValue;
                    }
                }


            if ($withSiteAccess && count($results) > 0) {
                $results["siteaccess-origin"] = $siteAccess;
            }
            return count($results) > 0? $results : null;
        } else {
            return isset(self::$parametersAndLocations[$parameterName]) ?
                self::$parametersAndLocations[$parameterName]: null;
        }
    }

    private static function getLocationsFromRewrittenSiteAccessParameter(
        string $newSiteAccess,
        array $originalParameterKeySegments
    ) {
        if ($originalParameterKeySegments[1] !== $newSiteAccess) {
            $originalParameterKeySegments[1] = $newSiteAccess;

            $newParameterTry = join(".", $originalParameterKeySegments);

            return isset(self::$parametersAndLocations[$newParameterTry]) ?
                self::$parametersAndLocations[$newParameterTry] : [];
        }

        return [];
    }
}
