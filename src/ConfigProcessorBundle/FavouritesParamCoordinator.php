<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use CJW\CJWConfigProcessor\src\Utility\Utility;
use Exception;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FavouritesParamCoordinator is responsible for providing methods and functionalities to form, determine and
 * retrieve parameters of the existing configuration as favourites.
 *
 * @package CJW\CJWConfigProcessor\src\ConfigProcessorBundle
 */
class FavouritesParamCoordinator
{

    /**
     * @var bool Boolean which states whether the coordinator has been intialized yet.
     */
    private static $initialized = false;
    /**
     * @var ContainerInterface The standard Symfony container created during the boot process by the kernel.
     */
    private static $symContainer;
    /**
     * @var PhpFilesAdapter Cache adapter to allow the coordinator to store the favourite parameters.
     */
    private static $cache;

    /**
     * Initializes the coordinator and instantiates all important attributes of the class in order for
     * it to function properly.
     *
     * @param ContainerInterface $symContainer The standard Symfony container created during the boot process by the kernel.
     */
    public static function initialize (ContainerInterface $symContainer)
    {
        if ($symContainer) {
            self::$symContainer = $symContainer;
        }

        if (!self::$cache) {
            try {
                $cacheDir = $symContainer->get("kernel")->getCacheDir()."/cjw/config-processor-bundle/";
                self::$cache = new PhpFilesAdapter("",0,$cacheDir);
            } catch (Exception $error) {
                self::$cache = new PhpFilesAdapter();
            }
        }

        self::$initialized = true;
    }

    /**
     * Retrieves a list of favourite parameters that have been set for the installation by the user.
     *
     * @param array $processedParameters An associative array of all processed parameters of the installation.
     * @param array $siteAccesses A list of site accesses to take into account, when retrieving or determining the favourites.
     *
     * @return array Returns either an array containing the favourite parameters or an empty array if non have been set or found.
     *
     * @throws \Psr\Cache\InvalidArgumentException Should an error occur with the caching mechanism behind the favourites.
     */
    public static function getFavourites (array $processedParameters, array $siteAccesses = [])
    {
        if (
            self::$symContainer->getParameter("cjw.favourite_parameters.allow") === true
        ) {
            $favouriteRetrievalProcessor = new CustomParamProcessor(
                self::$symContainer,
                $siteAccesses
            );

            $favouriteParameters =
                Utility::cacheContractGetOrSet("cjw_custom_favourite_parameters", self::$cache,
                    function () use ($favouriteRetrievalProcessor, $processedParameters) {
                    return self::getFavouritesThroughContainer(
                        $favouriteRetrievalProcessor,
                        $processedParameters
                    );
                });

            if (count($siteAccesses) > 0) {
                $favouriteParameters =
                    $favouriteRetrievalProcessor->scanAndEditForSiteAccessDependency($favouriteParameters);
            }

            return $favouriteParameters;
        }

        return [];
    }

    /**
     * Takes the existing favourites that have been set by the user and constructs a list of key segments from these
     * favourites (meaning only the keys of the favourite array and non of the values).
     *
     * <br>Example: ["favourite" => ["keys" => ["value" = "value"]]] will return ["favourite" => ["keys" => []]]
     *
     * @param array $processedParameters An associative array of all processed parameters of the installation.
     * @param array $siteAccesses A list of site accesses to take into account, when retrieving or determining the favourites.
     *
     * @return array Returns an array of favourite keys.
     *
     * @throws \Psr\Cache\InvalidArgumentException Should an error occur with the caching mechanism behind the favourites.
     */
    public static function getFavouriteKeyList (array $processedParameters, array $siteAccesses = [])
    {
        $favouritesToProcess =
            self::getFavourites($processedParameters, $siteAccesses);

        return Utility::removeSpecificKeySegment(
            "parameter_value",
            $favouritesToProcess
        );
    }

    /**
     * Allows setting specific parameters as favourites. The keys to be added must contain the entire
     * parameter key to be set as favourites as every entry of the array will be viewed as a selfcontained unit.
     *
     * <br>Example: ["first.favourite.parameter","second.favourite.parameter","third","fourth.parameter"]
     *
     * @param array $favouriteParameterKeys A list of parameter keys to be set as favourites.
     * @param array $processedParameters The processed Symfony configuration to search in with regards to the favourites.
     *
     * @throws \Psr\Cache\InvalidArgumentException Should an error occur with the caching mechanism behind the favourites.
     */
    public static function setFavourite (array $favouriteParameterKeys, array $processedParameters)
    {
        if (
            self::$symContainer->getParameter("cjw.favourite_parameters.allow") === true
        ) {
            $favouriteRetrievalProcessor = new CustomParamProcessor(self::$symContainer);

            $previousFavourites =
                Utility::cacheContractGetOrSet("cjw_custom_favourite_parameters", self::$cache,
                    function() use ($favouriteRetrievalProcessor, $processedParameters) {
                        return self::getFavouritesThroughContainer(
                             $favouriteRetrievalProcessor,
                            $processedParameters
                        );
                    }
                );

            if (
                self::$symContainer->getParameter("cjw.favourite_parameters.scan_parameters") === true
            ) {
                $favouriteParameterKeys =
                    $favouriteRetrievalProcessor->replacePotentialSiteAccessParts($favouriteParameterKeys);
            }


            $newFavourites = $favouriteRetrievalProcessor->getCustomParameters(
                $favouriteParameterKeys,
                $processedParameters
            );

            $uncommonFavourites = Utility::removeCommonParameters($newFavourites, $previousFavourites);

            if (count($uncommonFavourites[0]) > 0) {
                self::$cache->deleteItem("cjw_custom_favourite_parameters");
                Utility::cacheContractGetOrSet(
                    "cjw_custom_favourite_parameters",
                    self::$cache,
                    function() use ($previousFavourites, $newFavourites) {
                       return array_replace_recursive($previousFavourites, $newFavourites);
                    },
                    true
                );
            }
        }
    }

    /**
     * Allows removing specific parameters as a favourite. The keys to be removed must contain the entire
     * parameter key to be removed as favourites as every entry of the array will be viewed as a selfcontained unit.
     * When a given parameter is not part of the favourites, nothing will be done to the list.
     *
     * <br>Example: ["first.favourite.parameter","second.favourite.parameter","third","fourth.parameter"]
     *
     * @param array $favouritesToRemove An array of (string) parameter keys to be removed from the favourite parameters.
     * @param array $processedParameters The processed Symfony configuration to search in with regards to the favourites.
     *
     * @throws \Psr\Cache\InvalidArgumentException Should an error occur with the caching mechanism behind the favourites.
     */
    public static function removeFavourite(array $favouritesToRemove, array $processedParameters)
    {

        $favouriteRetrievalProcessor = new CustomParamProcessor(self::$symContainer);

        $previousFavourites =
            Utility::cacheContractGetOrSet("cjw_custom_favourite_parameters", self::$cache,
                function() use ($favouriteRetrievalProcessor, $processedParameters) {
                    return self::getFavouritesThroughContainer(
                        $favouriteRetrievalProcessor,
                        $processedParameters
                    );
                }
            );

        $currentFavourites = $previousFavourites;

        foreach ($favouritesToRemove as $favouriteKey) {
            $keySegments = explode(".",$favouriteKey);

            $currentFavourites =
                Utility::removeEntryThroughKeyList($previousFavourites, $keySegments);
        }

        $uncommonFavourites = Utility::removeCommonParameters($currentFavourites,$previousFavourites);

        if (count($uncommonFavourites[1]) > 0) {
            self::$cache->deleteItem("cjw_custom_favourite_parameters");

            Utility::cacheContractGetOrSet(
                "cjw_custom_favourite_parameters",
                self::$cache,
                function () use ($currentFavourites) {
                    return $currentFavourites;
                },
                true
            );
        }
    }

    /**
     * Supposed to retrieve the favourite parameters from cache, dictating the procedure in the case nothing has been
     * cached in order to create the cache entry.
     *
     * @param CustomParamProcessor $favouriteRetrievalProcessor The processor used to determine and retrieve the parameters that have been deemed as favourites.
     * @param array $processedParameters The processed Symfony configuration to search in with regards to the favourites.
     *
     * @return array|null Returns an array of favourites or null if none could be found.
     */
    private static function getFavouritesThroughContainer (CustomParamProcessor $favouriteRetrievalProcessor, array $processedParameters)
    {
        $favouriteKeys = self::$symContainer->getParameter("cjw.favourite_parameters.parameters");

        return $favouriteRetrievalProcessor->getCustomParameters(
            $favouriteKeys,
            $processedParameters
        );
    }

}
