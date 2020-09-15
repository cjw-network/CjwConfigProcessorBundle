<?php


namespace App\CJW\ConfigProcessorBundle\src;


use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class SiteAccessParamProcessor
{
    /**
     * Contains the site access the processor is supposed to work for / work with and display it's content.
     * @var string
     */
    private $workingSiteAccess;

    /**
     * Stores all the parameters only once with their actual current value and does not feature any duplicates
     * of the same parameter from a different siteaccess.
     *
     * @var array
     */
    private $uniqueSiteAccessParameters;

    /**
     * Stores all the parameters that are siteaccess dependent including their values as set for their scope.
     *
     * @var array
     */
    private $siteAccessParameters;

    /**
     * Holds the ezplatform / -systems config resolver with which to work out the values for the parameters.
     *
     * @var ConfigResolverInterface
     */
    private $ezConfigResolver;

    public function __construct(ConfigResolverInterface $resolver)
    {
        $this->ezConfigResolver = $resolver;
        $this->siteAccessParameters = [];
        $this->uniqueSiteAccessParameters = [];
    }

    /**
     * @param ConfigResolverInterface $ezConfigResolver
     */
    public function setEzConfigResolver(ConfigResolverInterface $ezConfigResolver): void
    {
        $this->ezConfigResolver = $ezConfigResolver;
    }

    /**
     * @return array
     */
    public function getSiteAccessParameters(): array
    {
        return $this->siteAccessParameters;
    }

    /**
     * @return array
     */
    public function getUniqueSiteAccessParameters(): array
    {
        return $this->uniqueSiteAccessParameters;
    }

    /**
     * Function to filter and resolve all parameters given to the function via a list of siteaccesses.
     * That means that only values belonging to siteaccesses will be kept in the array and processed
     * further and their values will be resolved. Internally in this object, both the unique and
     * resolved parameters are stored and the original list of all siteaccess dependent parameters and their values.
     *
     * @param array $siteAccesses The list of siteaccesses to filter for in the parameters.
     * @param array $parameters The parameters to be filtered and processed.
     * @return array Returns an array that possesses only unique parameters and their current value.
     */
    public function processSiteAccessBased(array $siteAccesses, array $parameters) {
        $this->siteAccessParameters = $this->filterForSiteAccess($siteAccesses,$parameters);
        $this->uniqueSiteAccessParameters = $this->provideUniqueParameters($this->siteAccessParameters);
        try {
            $this->uniqueSiteAccessParameters = $this->resolveParameters($this->uniqueSiteAccessParameters);
        } catch (Exception $error) {
            sprintf(`Something went wrong while trying to resolve the parameter values. ${$error}`);
        }

        return $this->uniqueSiteAccessParameters;
    }

    /**
     * Takes a given list of siteaccesses and searches in the given parameters array for every
     * parameter that features at least one of the accesses. If one or more are found, than these
     * parts of the parameter are being pushed onto the result.
     *
     * @param array $siteAccesses The list of siteaccesses to search for.
     * @param array $parameters The array of parameters in which to search.
     * @return array Returns the resulting array which consists of all found parameter parts.
     */
    private function filterForSiteAccess (array $siteAccesses, array $parameters) {
        $resultArray = [];

        foreach ($parameters as $parameter) {
            foreach($siteAccesses as $siteAccess) {
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
     * access. As a result the array only contains unique parameters for further processing.
     *
     * @param array $siteAccessParameters The parameters to be processed.
     * @return array Returns an array that includes only unique parameters.
     */
    private function provideUniqueParameters(array $siteAccessParameters) {
        $uniqueParameters = $siteAccessParameters;
        $encounteredParamNames = [];

        foreach (array_keys($uniqueParameters) as $namespace) {
            foreach ($uniqueParameters[$namespace] as $siteaccess) {
                foreach ($siteaccess->getParameters() as $parameter) {
                    if ($parameter instanceof ProcessedParamModel) {
                        $fullnames = $parameter->getAllFullParameterNames();

                        foreach ($fullnames as $fullname) {

                            if (!isset($encounteredParamNames[$namespace])) {
                                $encounteredParamNames[$namespace] = [];
                            }

                            if (!in_array($fullname, array_keys($encounteredParamNames[$namespace]))) {
                                $encounteredParamNames[$namespace][$fullname] = "";
                            }
                        }
                    }
                }
            }
        }

        return $encounteredParamNames;
    }

    /**
     * Takes the filteredParameters and tries to resolve them to their current value in the site-access.
     *
     * @param array $filteredParameters The filtered parameter list which is being resolved to the actual currently set parameters.
     * @return array Returns the resolved Parameters.
     *
     * @throws Exception Throws an exception if there hasn't been a valid configResolver set for the object.
     */
    private function resolveParameters(array $filteredParameters) {
        if (!$this->ezConfigResolver) {
            throw new Exception("No configResolver has been set for this object.");
        }

        foreach(array_keys($filteredParameters) as $namespace) {
            foreach(array_keys($filteredParameters[$namespace]) as $parameterName) {
                try {
                    $filteredParameters[$namespace][$parameterName] = $this->ezConfigResolver->getParameter($parameterName,$namespace);
                } catch (Exception $error) {
                    unset($filteredParameters[$namespace][$parameterName]);
                }
            }
        }

        return $filteredParameters;
    }
}
