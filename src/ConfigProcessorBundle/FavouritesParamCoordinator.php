<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use CJW\CJWConfigProcessor\src\Utility\Utility;
use Exception;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FavouritesParamCoordinator
{

    private static $initialized = false;
    private static $symContainer;
    private static $cache;

    public static function initialize (
        ContainerInterface $symContainer
    ) {
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

    public static function getFavourites (
        array $processedParameters,
        array $siteAccesses = []
    ): array {
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

    public static function getFavouriteKeyList (
        array $processedParameters,
        array $siteAccesses = []
    ): array {

        $favouritesToProcess =
            self::getFavourites($processedParameters, $siteAccesses);

        return Utility::removeSpecificKeySegment(
            "parameter_value",
            $favouritesToProcess
        );
    }

    public static function setFavourite (
        array $favouriteParameterKeys,
        array $processedParameters
    )
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

    public static function removeFavourite(
        array $favouritesToRemove,
        array $processedParameters
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

    private static function getFavouritesThroughContainer (
        CustomParamProcessor $favouriteRetrievalProcessor,
        array $processedParameters
    ): array {
        $favouriteKeys = self::$symContainer->getParameter("cjw.favourite_parameters.parameters");

        return $favouriteRetrievalProcessor->getCustomParameters(
            $favouriteKeys,
            $processedParameters
        );
    }

}
