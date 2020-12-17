<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class LocationRetrievalCoordinator is the instigator of the entire location retrieval process and responsible for
 * creating and using both the custom kernel and helper classes to determine, keep track of and store the locations
 * of and for every determined parameter.
 *
 * @package CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle
 */
class LocationRetrievalCoordinator
{

    /**
     * @var LoadInitializer A custom kernel that initiates the entire custom loading process.
     */
    private static $customConfigLoader;

    /**
     * @var array An array which not only stores the parameters, but also the paths they have been read from (including the values set there)
     */
    public static $parametersAndLocations;

    /**
     * @var PhpFilesAdapter A cache which is supposed to store the parameters that have been parsed.
     */
    private static $cache;

    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * "Initiates" the class and sets all missing and non-instantiated attributes of the class prior to the rest
     * of its functions being called.
     */
    public static function initializeCoordinator(): void
    {
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
            if (is_array(self::$parametersAndLocations) && count(self::$parametersAndLocations) > 0)
            {
                self::$cache->delete("cjw_parameters_and_locations");
                self::$cache->delete("cjw_processed_param_objects");
                self::$cache->delete("cjw_processed_params");
                self::$cache->delete("cjw_processing_timestamp");
            }

            // Then store the presumably "new" parameters
            self::$parametersAndLocations =
                self::$cache->get("cjw_parameters_and_locations", function (ItemInterface $item) {
                    $item->set(self::$parametersAndLocations);

                    return self::$parametersAndLocations;
                });
        }catch (InvalidArgumentException $e) {
        }

        self::$initialized = true;
    }

    /**
     * Retrieves all parameters and the associated locations which have been retrieved by the class.
     *
     * @return array An associative array, which contains the parameters as first keys, then the different paths and the values that have been set in those files.
     */
    public static function getParametersAndLocations(): array
    {
        if (!self::$initialized) {
            self::initializeCoordinator();
        }

        return self::$parametersAndLocations;
    }

    /**
     * This functionality allows to retrieve all locations specific to one parameter given to the function. This can
     * be done in a site access context too, where all site access versions of the given parameter will be looked at
     * as well.
     *
     * @param string $parameterName The name of the parameter who's locations should be retrieved.
     * @param array|null $siteAccessGroups An array of the site access groups that exist in the current installation (required to determine site access groups versions of the parameter).
     * @param bool $withSiteAccess A boolean which states whether the parameter should be viewed in a site access context. Set to true, all site access versions of the given parameter are looked at.
     *
     * @return array|null An array of locations for the parameter of null if nothing could be found.
     */
    public static function getParameterLocations (string $parameterName, array $siteAccessGroups = null, bool $withSiteAccess = false): ?array
    {
        if (!self::$initialized) {
            self::initializeCoordinator();
        }

        return self::getLocationsForSpecificParameter($parameterName, $siteAccessGroups, $withSiteAccess);
    }

    /**
     * Returns the internal array which keeps track of all encountered locations without any connection to
     * the parameters, values or other information. It resembles a plain "stack" of locations.
     *
     * @param string $parameterName The name of the parameter who's locations should be retrieved.
     * @param array|null $siteAccessGroups An array of the site access groups that exist in the current installation (required to determine site access groups versions of the parameter).
     * @param bool $withSiteAccess A boolean which states whether the parameter should be viewed in a site access context. Set to true, all site access versions of the given parameter are looked at.
     *
     * @return array Returns an array which is filled with all encountered locations during the configuration-loading-process.
     */
    private static function getLocationsForSpecificParameter (string $parameterName, array $siteAccessGroups = null, bool $withSiteAccess = false): ?array
    {
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

    /**
     * This function takes the original parameter name which has been split up by the "." (dots) and a given site access
     * by which to look at it and creates a new parameter name through the given parts. Afterwards it checks for whether
     * locations exist for that specific parameter.
     *
     * @param string $newSiteAccess The site access with which to construct the parameter name.
     * @param array $originalParameterKeySegments An array of all segments of the parameter key.
     *
     * @return array Returns an array which includes the found locations for the new parameter or an empty one if nothing could be found.
     */
    private static function getLocationsFromRewrittenSiteAccessParameter(string $newSiteAccess, array $originalParameterKeySegments): array
    {
        if ($originalParameterKeySegments[1] !== $newSiteAccess) {
            $originalParameterKeySegments[1] = $newSiteAccess;

            $newParameterTry = join(".", $originalParameterKeySegments);

            return isset(self::$parametersAndLocations[$newParameterTry]) ?
                self::$parametersAndLocations[$newParameterTry] : [];
        }

        return [];
    }
}
