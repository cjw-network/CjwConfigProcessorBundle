<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

/**
 * Class SiteAccessParamProcessor serves to take the processed parameters and determine not only which parameters are
 * active within that siteaccess, but also what value that parameter holds under these circumstances and reformat them
 * for output.
 *
 * @package CJW\CJWConfigProcessor\ConfigProcessorBundle\src
 */
class SiteAccessParamProcessor
{
    /**
     * @var ConfigResolverInterface Holds the ezplatform / -systems config resolver with which to work out the values for the parameters.
     */
    private $ezConfigResolver;

    public function __construct(ConfigResolverInterface $resolver)
    {
        $this->ezConfigResolver = $resolver;
    }

    /**
     * @param ConfigResolverInterface $ezConfigResolver Set the config resolver to be used by the class.
     */
    public function setEzConfigResolver(ConfigResolverInterface $ezConfigResolver)
    {
        $this->ezConfigResolver = $ezConfigResolver;
    }

    /**
     * Function to filter and resolve all parameters given to the function via a list of siteaccesses.
     * That means that only values belonging to siteaccesses will be kept in the array and processed
     * further and their values will be resolved.
     *
     * @param array $siteAccesses The list of siteaccesses to filter for in the parameters.
     * @param array $parameters The parameters to be filtered and processed.
     * @param string|null $scope Optional parameter which determines whether a specific scope should be used for determining the parameter values or simply the current one.
     *
     * @return array Returns an array that possesses only unique parameters and their current value.
     */
    public function processSiteAccessBased(array $siteAccesses, array $parameters,$scope = null)
    {
        $siteAccessParameters = $this->filterForSiteAccess($siteAccesses, $parameters);
        $uniqueSiteAccessParameters =  $this->provideUniqueParameters($siteAccessParameters);

        try {
            if (!$scope) {
                $uniqueSiteAccessParameters = $this->resolveParameters($uniqueSiteAccessParameters);
            } else {
                $uniqueSiteAccessParameters = $this->resolveParametersWithScope($uniqueSiteAccessParameters,$scope);
            }
        } catch (Exception $error) {
            sprintf(`Something went wrong while trying to resolve the parameter values. ${$error}`);
        }

        $uniqueSiteAccessParameters = $this->reformatForOutput($uniqueSiteAccessParameters);
        return $uniqueSiteAccessParameters;
    }

    /**
     * Takes a given list of siteaccesses and searches in the given parameters array for every
     * parameter that features at least one of the accesses. If one or more are found, than these
     * parts of the parameter are being pushed onto the result.
     *
     * @param array $siteAccesses The list of siteaccesses to search for.
     * @param array $parameters The array of parameters in which to search.
     *
     * @return array Returns the resulting array which consists of all found parameter parts.
     */
    private function filterForSiteAccess(array $siteAccesses, array $parameters)
    {
        $resultArray = [];

        foreach ($parameters as $parameter) {
            foreach ($siteAccesses as $siteAccess) {
                if ($parameter instanceof ProcessedParamModel) {
                    $result = $parameter->filterForSiteAccess($siteAccess);
                    if ($result) {
                        $resultArray[$parameter->getKey()][$result->getKey()] = $result;
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Function which removes every parameter that is already present in the array under a different site-
     * access. As a result, the array only contains unique parameters for further processing.
     *
     * @param array $siteAccessParameters The parameters to be processed.
     *
     * @return array Returns an array that includes only unique parameters.
     */
    private function provideUniqueParameters(array $siteAccessParameters)
    {
        $uniqueParameters = $siteAccessParameters;
        $encounteredParamNames = [];

        foreach ($uniqueParameters as $namespace => $value) {
            foreach ($value as $siteaccess) {
                foreach ($siteaccess->getParameters() as $parameter) {
                    if ($parameter instanceof ProcessedParamModel) {
                        $fullParameterNames = $parameter->getAllFullParameterNames();

                        foreach ($fullParameterNames as $fullname) {

                            // If an array under the current namespace has not yet been initialised, initialise it
                            if (!isset($encounteredParamNames[$namespace])) {
                                $encounteredParamNames[$namespace] = [];
                            }

                            if (!in_array($fullname, array_keys($encounteredParamNames[$namespace]))) {
                                $encounteredParamNames[$namespace][$fullname] = ["parameter_value" => ""];
                            }
                        }
                    }
                }
            }
        }

        return $encounteredParamNames;
    }

    /**
     * Takes the filtered parameters and tries to resolve them to their current value in the site-access.
     *
     * @param array $filteredParameters The filtered parameter list which is being resolved to the actual currently set parameters.
     * @return array Returns the resolved Parameters.
     *
     * @throws Exception Throws an exception if there hasn't been a valid configResolver set for the object.
     */
    private function resolveParameters(array $filteredParameters)
    {
        if (!$this->ezConfigResolver) {
            throw new Exception("No configResolver has been set for this object.");
        }

        foreach ($filteredParameters as $namespace => $namespaceValue) {
            foreach ($namespaceValue as $parameterName => $parameterValue) {
                try {
                    $result = $this->ezConfigResolver->getParameter($parameterName, $namespace);
                    if (is_array($result) && count($result) === 1 && key_exists(0,$result)) {
                        $result = $result[0];
                    }

                    $filteredParameters[$namespace][$parameterName]["parameter_value"] = $result;
                } catch (Exception $error) {
                    unset($filteredParameters[$namespace][$parameterName]);
                }
            }
        }

        return $filteredParameters;
    }

    /**
     * Technically does the same as {@see resolveParameters}
     * but includes the given scope and thus ensures that parameters can be parsed with regards to
     * a specific given site access.
     *
     * might be reworked to better fit the bundle's coding standard.
     *
     * @param array $filteredParameters The filtered parameter list which is being resolved to the actual currently set parameters.
     * @param string $scope The specific site access for which to retrieve the parameter value.
     *
     * @return array Returns the resolved Parameters.
     *
     * @throws Exception Throws an exception if there hasn't been a valid configResolver set for the object.
     */
    private function resolveParametersWithScope(array $filteredParameters, $scope)
    {
        if (!$this->ezConfigResolver) {
            throw new Exception("No configResolver has been set for this object.");
        }

        foreach ($filteredParameters as $namespace => $namespaceValue) {
            foreach ($namespaceValue as $parameterName => $parameterValue) {
                try {
                    $filteredParameters[$namespace][$parameterName]["parameter_value"] = $this->ezConfigResolver->getParameter($parameterName, $namespace, $scope);
                } catch (Exception $error) {
                    unset($filteredParameters[$namespace][$parameterName]);
                }
            }
        }

        return $filteredParameters;
    }

    /**
     * Rearranges the array's keys in alphabetical order for easier navigation.
     *
     * @param array $processedSiteAccessParameters Parameters to be sorted.
     * 
     * @return array Returns the reformatted array.
     */
    private function reformatForOutput(array $processedSiteAccessParameters)
    {
        ksort($processedSiteAccessParameters, SORT_STRING);

        foreach ($processedSiteAccessParameters as $namespace => $namespaceValue) {
            ksort($processedSiteAccessParameters[$namespace], SORT_STRING);
        }

        return array_filter($processedSiteAccessParameters);
    }
}
