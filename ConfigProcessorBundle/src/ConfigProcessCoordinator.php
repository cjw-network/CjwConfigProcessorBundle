<?php


namespace App\CJW\ConfigProcessorBundle\src;


use App\CJW\ConfigProcessorBundle\ParameterAccessBag;
use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigProcessCoordinator
{

    /** @var ContainerInterface */
    private static $symContainer;

    /** @var ConfigResolverInterface */
    private static $ezConfigResolver;

    /** @var ConfigProcessor */
    private static $configProcessor;

    /** @var SiteAccessParamProcessor */
    private static $siteAccessParamProcessor;

    /** @var RequestStack */
    private static $symRequestStack;

    /** @var PhpFilesAdapter */
    private static $cache;

    /**
     * Contains all the processed parameters categorized after their namespaces and other keys within their name
     * down to the actual parameter.
     *
     * @var array
     */
    private static $processedParameters;

    /**
     * Contains all parameters that have been matched to the site access of the current request.
     * These mostly resort to parameters already present in the processedParameters, but only the ones
     * specific to the current site access.
     * @see $processedParameters
     *
     * @var array
     */
    private static $siteAccessParameters;

    /** @var bool  */
    private static $initialized = false;

    /**
     * @param ContainerInterface $symContainer
     * @param ConfigResolverInterface $ezConfigResolver
     * @param RequestStack $symRequestStack
     */
    public static function initializeCoordinator (
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    ): void {

        if (!self::$symContainer && $symContainer) {
            self::$symContainer = $symContainer;
        }

        if (!self::$ezConfigResolver && $ezConfigResolver) {
            self::$ezConfigResolver = $ezConfigResolver;
        }

        if (!self::$symRequestStack && $symRequestStack) {
            self::$symRequestStack = $symRequestStack;
        }

        if (!self::$configProcessor) {
            self::$configProcessor = new ConfigProcessor();
        }

        if (!self::$siteAccessParamProcessor) {
            self::$siteAccessParamProcessor = new SiteAccessParamProcessor(self::$ezConfigResolver);
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
     * @throws Exception
     */
    public static function startProcess () {

        if (!self::$initialized) {
            throw new Exception("The 'ConfigProcessCoordinator' has not been initialized! Please make sure you ran the 'initializeCoordinator()'-function at least once!");
        }

        try {
            $request = self::$symRequestStack->getCurrentRequest();

            self::validateCachedItems();

            self::$processedParameters = self::$cache->get("cjw_processed_params", function() {
                return self::parseContainerParameters();
            });

            if ($request) {

                self::$siteAccessParameters = self::$cache->get("cjw_site_access_parameters", function() {
                    return self::getParametersForSiteAccess();
                });

            }
        } catch (Exception $error) {
            print(`Something went wrong while trying to parse the parameters: ${$error}.`);
        } catch (InvalidArgumentException $e) {
            print(`An error occured while trying to access caching for the parameters: ${$e}.`);
        }
    }

    /**
     * Takes the processed parameters and searches for all parameters and their values that
     * belong to the current or a given site access.
     *
     * @param string|null $siteAccess Optional argument which states which site access should be used for the parameter value retrieval.
     * @return array Returns an array which includes the site access parameters.
     * @throws InvalidArgumentException Throws an exception, when the Coordinator has not been initialized prior to calling this function.
     */
    public static function getParametersForSiteAccess(string $siteAccess = null): array {
        if ($siteAccess) {
            $siteAccess = strtolower($siteAccess);
        }

        $processedParamObj = self::$cache->get(
            "cjw_processed_param_objects",
            function() {
                return self::$configProcessor->getProcessedParameters();
            }
        );

        $siteAccessList = self::getSiteAccesses($siteAccess);

        $saParameters = self::$siteAccessParamProcessor->processSiteAccessBased(
            $siteAccessList,
            $processedParamObj,
            $siteAccess
        );

        $customParameters = self::getCustomParameters($siteAccessList);

        return array_replace_recursive($saParameters,$customParameters);
    }

    /**
     * Gets the processed parameters array which contains the reformatted and sorted parameters.
     *
     * @return array
     * @throws Exception
     */
    public static function getProcessedParameters(): array
    {
        if (!self::$processedParameters) {
            self::startProcess();
        }

        return self::$processedParameters;
    }

    /**
     * Retrieves the parameters for the current site access.
     *
     * @return array
     * @throws Exception
     */
    public static function getSiteAccessParameters(): array
    {
        if (!self::$siteAccessParameters) {
            self::startProcess();
        }

        return self::$siteAccessParameters?? [];
    }

    public static function getSiteAccessListForController(string $specificSiteAccess = null): array {
        return self::getSiteAccesses($specificSiteAccess);
    }

    /*****************************************************************************************
     *
     * Private methods of the class which are called by the public functions.
     *
     *****************************************************************************************/

    /**
     * Parses the internal symfony parameters and provides the formatted parameters and options
     * as an array to the class.
     *
     * @return array Returns a hierarchical associative array that features every parameter sorted after their keys.
     * @throws Exception Throws an error if something went wrong while trying to parse the parameters.
     */
    private static function parseContainerParameters() {
        $parameters = new ParameterAccessBag(self::$symContainer);
        $parameters = $parameters->getParameters();

        if ($parameters && is_array($parameters)) {
            self::$processedParameters = self::$configProcessor->processParameters($parameters);
        } else {
            throw new Exception("Something went wrong while trying to parse the parameters of the container.");
        }

        return self::$processedParameters;
    }

    /**
     * Simply goes into the ezpublish-parameter to get the list of current site accesses that exist in the
     * parameter.
     *
     * @param string|null $desiredSiteAccess Optional parameter which dictates whether only the default SiteAccesses and the given one will be added or all available ones are added.
     * @return array Returns all found siteAccesses in an array.
     * @throws Exception
     */
    private static function getSiteAccesses(string $desiredSiteAccess = null): array {
        if (!self::$processedParameters) {
            self::startProcess();
        }

        try {
            if (!$desiredSiteAccess) {
                $siteAccesses = self::$processedParameters["ezpublish"]["siteaccess"]["list"]["parameter_value"];
                array_push($siteAccesses, ...array_keys(self::$processedParameters["ezpublish"]["siteaccess"]["groups"]["parameter_value"]));
            } else {
                $siteAccesses = array($desiredSiteAccess);
                array_push($siteAccesses,...self::$processedParameters["ezpublish"]["siteaccess"]["groups_by_siteaccess"]["parameter_value"][$desiredSiteAccess]);
            }

            array_push($siteAccesses, "default", "global");
        } catch (Exception $error) {
            // Fallback SAs if the others are not accessible via the array route
            $siteAccesses = array("default","global");
        }
        return $siteAccesses;
    }

    private static function getCustomParameters (array $siteAccessList) {
        if (
            self::$symContainer->hasParameter("cjw.custom_site_access_parameters.active") &&
            self::$symContainer->getParameter("cjw.custom_site_access_parameters.active") === true &&
            self::$symContainer->hasParameter("cjw.custom_site_access_parameters.parameters")
        ) {
            $customParameterKeys = self::$symContainer->getParameter("cjw.custom_site_access_parameters.parameters");
            $processedParameters = self::$processedParameters;

            $customParametersProcessor = new CustomSiteAccessParamProcessor(
                self::$symContainer,
                $siteAccessList
            );

            $customParameters = $customParametersProcessor->getCustomParameters($customParameterKeys,$processedParameters);

            if (
                self::$symContainer->hasParameter("cjw.custom_site_access_parameters.scan_parameters") &&
                self::$symContainer->getParameter("cjw.custom_site_access_parameters.scan_parameters") === true
            ) {
                return $customParametersProcessor->scanAndEditForSiteAccessDependency($customParameters);
            } else {
                return $customParameters;
            }
        }

        return [];
    }

    /**
     * Checks whether the required items of the service are already present in the cache or whether they
     * are not and acts accordingly in order to assure that the processing takes place with the current
     * and valid parameters and values.
     */
    private static function validateCachedItems() {
        // If there is no stored parameter list in object form, then undo the rest of the cache
        try {
            self::$cache->prune();

            if (!self::$cache->hasItem("cjw_processed_param_objects")) {
                self::$cache->delete("cjw_processed_params");
                self::$cache->delete("cjw_site_access_parameters");
            } else if (!self::$cache->hasItem("cjw_processed_params")) {
                self::$cache->delete("cjw_processed_param_objects");
            }

        } catch (InvalidArgumentException $e) {
            print(`Accessing the cache components of the service has led to errors: ${$e}`);
        }

    }

}

