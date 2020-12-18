<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use CJW\CJWConfigProcessor\src\Utility\Utility;
use DateTime;
use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigProcessCoordinator
{

    /**
     * @var ContainerInterface The standard Symfony container, which is created by the kernel on boot.
     */
    private static $symContainer;

    /**
     * @var ConfigResolverInterface
     */
    private static $ezConfigResolver;

    /**
     * @var ConfigProcessor The processor with which the configuration is processed and parsed into an associative, hierarchical array.
     */
    private static $configProcessor;

    /**
     * @var SiteAccessParamProcessor The processor responsible for determining site access specific parameters and parsing them as such.
     */
    private static $siteAccessParamProcessor;

    /**
     * @var RequestStack The request stack with the current, pending request.
     */
    private static $symRequestStack;

    /**
     * @var PhpFilesAdapter The cache with which the results of all the processes are stored.
     */
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

    /**
     * @var bool Simply describes whether the class and its various internal attributes have been initialized.
     */
    private static $initialized = false;

    /**
     * @var string The time the processed parameters have last been updated.
     */
    private static $lastUpdated;

    /**
     * Function to set up and first initiate the coordinator in order to allow for it to perform its processes
     * without issue. This also sets up the variables and attributes of the class.
     *
     * @param ContainerInterface $symContainer The standard Symfony container, which has been created by the standard kernel.
     * @param ConfigResolverInterface $ezConfigResolver A config resolver by ez to determine site access parameters.
     * @param RequestStack $symRequestStack Contains the current, pending request.
     */
    public static function initializeCoordinator (ContainerInterface $symContainer, ConfigResolverInterface $ezConfigResolver, RequestStack $symRequestStack)
    {

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
     * Responsible for starting the entire processing and parsing of the internal Symfony configuration and
     * will create first versions of the processed parameters and the site access parameters.
     *
     * @throws Exception Throws an execption, when the coordinator has not been initialized before calling this function.
     */
    public static function startProcess ()
    {

        if (!self::$initialized) {
            throw new Exception(
                "The 'ConfigProcessCoordinator' has not been initialized! " .
                "Please make sure you ran the 'initializeCoordinator()'-function at least once!"
            );
        }

        try {
            $request = self::$symRequestStack->getCurrentRequest();

            self::validateCachedItems();

            self::$processedParameters =
                Utility::cacheContractGetOrSet("cjw_processed_params", self::$cache, function() {
                    return self::parseContainerParameters();
                });

            if ($request) {
                self::$siteAccessParameters =
                    Utility::cacheContractGetOrSet("cjw_site_access_parameters", self::$cache, function() {
                        return self::getParametersForSiteAccess();
                    });
            }

            self::$lastUpdated =
                Utility::cacheContractGetOrSet("cjw_processing_timestamp", self::$cache, function() {
                    $currentDate = new DateTime();
                    return $currentDate->format("Y-m-d H:i");
                });
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
     *
     * @return array Returns an array which includes the site access parameters.
     *
     * @throws InvalidArgumentException Throws an exception, when the Coordinator has not been initialized prior to calling this function.
     */
    public static function getParametersForSiteAccess($siteAccess = null)
    {
        if ($siteAccess) {
            $siteAccess = strtolower($siteAccess);
        }

        $processedParamObj =
            Utility::cacheContractGetOrSet("cjw_processed_param_objects", self::$cache,function() {
                return self::$configProcessor->getProcessedParameters();
            });

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
     * @return array The processed parameters as a hierarchical, associative array.
     *
     * @throws Exception Throws an exception, when the coordinator has not been intialized before calling this function.
     */
    public static function getProcessedParameters()
    {
        if (!self::$processedParameters) {
            self::startProcess();
        }

        return self::$processedParameters;
    }

    /**
     * Retrieves the parameters for the current site access.
     *
     * @return array Returns the site access specific parameters in a hierarchical, associative array.
     *
     * @throws Exception Throws an exception, when the coordinator has not been intialized before calling this function.
     */
    public static function getSiteAccessParameters()
    {
        if (!self::$siteAccessParameters) {
            self::startProcess();
        }

        return self::$siteAccessParameters?? [];
    }

    /**
     * Assembles and returns a list of all site accesses of the current installation.
     *
     * @param string|null $specificSiteAccess Can be filtered for a specific site access to get only the site accesses active, with the given one.
     *
     * @return string[] Returns an array of the found site accesses as strings.
     *
     * @throws Exception Throws an exception, when the coordinator has not been intialized before calling this function.
     */
    public static function getSiteAccessListForController($specificSiteAccess = null)
    {
        return self::getSiteAccesses($specificSiteAccess);
    }

    /**
     * Does what the name states: Returns the timestamp for when the processed parameters have last been updated.
     *
     * @return string Return the timestamp as a string.
     *
     * @throws Exception Throws an exception, when the coordinator has not been intialized before calling this function.
     */
    public static function getTimeOfLastUpdate ()
    {
        if (!self::$lastUpdated) {
            self::startProcess();
        }

        return self::$lastUpdated;
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
     *
     * @throws Exception Throws an error if something went wrong while trying to parse the parameters.
     */
    private static function parseContainerParameters()
    {
        $parameters = new ParameterAccessBag(self::$symContainer);
        $parameters = $parameters->getParameters();

        if ($parameters && is_array($parameters)) {
            self::$processedParameters =
                self::$configProcessor->processParameters($parameters);
        } else {
            throw new Exception(
                "Something went wrong while trying to parse the parameters of the container."
            );
        }

        return self::$processedParameters;
    }

    /**
     * Simply goes into the ezpublish-parameter to get the list of current site accesses that exist in the
     * parameter.
     *
     * @param string|null $desiredSiteAccess Optional parameter which dictates whether only the default SiteAccesses and the given one will be added or all available ones are added.
     *
     * @return array Returns all found siteAccesses in an array.
     *
     * @throws Exception Throws an exception, when the coordinator has not been intialized before calling this function.
     */
    private static function getSiteAccesses($desiredSiteAccess = null)
    {
        if (!self::$processedParameters) {
            self::startProcess();
        }

        try {
            if (!$desiredSiteAccess) {
                $siteAccesses =
                    self::$processedParameters["ezpublish"]["siteaccess"]["list"]["parameter_value"];

                array_push(
                    $siteAccesses,
                    ...array_keys (
                        self::$processedParameters["ezpublish"]["siteaccess"]["groups"]["parameter_value"]
                    )
                );
            } else {
                $siteAccesses = array($desiredSiteAccess);
                $siteAccessGroups =
                    self::$processedParameters["ezpublish"]["siteaccess"]["groups"]["parameter_value"];
                $siteAccessGroups = array_keys($siteAccessGroups);

                if (!in_array($desiredSiteAccess,$siteAccessGroups)) {
                    array_push(
                        $siteAccesses,
                        ...self::$processedParameters["ezpublish"]["siteaccess"]["groups_by_siteaccess"]["parameter_value"][$desiredSiteAccess]
                    );
                }

            }

            array_push(
                $siteAccesses,
                "default",
                "global"
            );
        } catch (Exception $error) {
            // Fallback SAs if the others are not accessible via the array route
            $siteAccesses = array("default","global");
        }
        return $siteAccesses;
    }

    /**
     * Responsible for returning the custom parameters which have been set by the user via the configuration
     * (if the feature has been enabled).
     *
     * @param array $siteAccessList A list of site accesses for which the parameters are supposed to be looked at.
     *
     * @return array Returns an associative, hierarchical array of custom parameters.
     */
    private static function getCustomParameters (array $siteAccessList)
    {
        if (
            self::$symContainer->getParameter("cjw.custom_site_access_parameters.active") === true
        ) {
            $customParameterKeys =
                self::$symContainer->getParameter("cjw.custom_site_access_parameters.parameters");
            $processedParameters = self::$processedParameters;

            $customParametersProcessor = new CustomParamProcessor(
                self::$symContainer,
                $siteAccessList
            );

            $customParameters =
                $customParametersProcessor->getCustomParameters(
                    $customParameterKeys,
                    $processedParameters
                );

            if (
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
    private static function validateCachedItems()
    {
        // If there is no stored parameter list in object form, then undo the rest of the cache
        try {
            self::$cache->prune();

            if (!self::$cache->hasItem("cjw_processed_param_objects")) {
                self::$cache->deleteItem("cjw_processed_params");
                self::$cache->deleteItem("cjw_site_access_parameters");
                self::$cache->deleteItem("cjw_processing_timestamp");
            } else if (!self::$cache->hasItem("cjw_processed_params")) {
                self::$cache->deleteItem("cjw_processed_param_objects");
                self::$cache->deleteItem("cjw_processing_timestamp");
            }

        } catch (InvalidArgumentException $e) {
            print(`Accessing the cache components of the service has led to errors: ${$e}`);
        }
    }
}

