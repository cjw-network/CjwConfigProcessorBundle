<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomParamProcessor is responsible for processing parameters and keys for parameters, which have been
 * set directly by the user via the config and it provides various helper function to retrieve, parse, scan and edit
 * the parameters (also when it comes to site accesses).
 *
 * @package CJW\CJWConfigProcessor\src\ConfigProcessorBundle
 */
class CustomParamProcessor
{
    /**
     * @var ContainerInterface The standard Symfony container, which has been created by the kernel during the boot process.
     */
    private $symContainer;
    /**
     * @var array A list of all site accesses for which to edit and scan the given parameters.
     */
    private $currentActiveSiteAccessList;
    /**
     * @var array A list of all site accesses available in the current installation.
     */
    private $allSiteAccesses;

    public function __construct(ContainerInterface $symContainer = null, array $siteAccessList = [])
    {
        $this->symContainer = $symContainer;
        $this->currentActiveSiteAccessList = $siteAccessList;

        if ($this->symContainer) {
            $this->constructListOfAllSiteAccesses();
        }
    }

    /**
     * Allows the site access list for which to potentially filter the parameters to be set after constructing the
     * class.
     *
     * @param array $siteAccessList The list of site accesses to filter to be set as filters.
     */
    public function setSiteAccessList (array $siteAccessList)
    {
        if (count($siteAccessList) > 0) {
            $this->currentActiveSiteAccessList = $siteAccessList;
        }
    }

    /**
     * Build and returns a list of custom parameters (in the format the rest of the processed parameters is in) based
     * on the given list of parameter keys.
     *
     * @param array $customParameterKeys A list of parameter keys as strings.
     * @param array $processedParameters A list of the processed parameters of the entire Symfony configuration.
     *
     * @return array|null Returns an array of custom parameters and null if no parameters could be found via the given keys.
     */
    public function getCustomParameters (array $customParameterKeys, array $processedParameters)
    {
        $customParameters = [];

        foreach ($customParameterKeys as $customKey) {
            $keyPartArray = explode(".", $customKey);

            if (count($keyPartArray) > 0) {
                $result = $this->getParameterThroughParts($keyPartArray, $processedParameters);

                if (count ($result) > 0) {
                    $key = array_keys($result)[0];

                    if (!isset($customParameters[$key])) {
                        $customParameters[$key] = $result[$key];
                    } else {
                        $customParameters = array_replace_recursive($customParameters, $result);
                    }
                }
            }
        }

        return $customParameters;
    }

    /**
     * Takes a list of parameter keys (as strings) and checks them for any potential site access segments within the
     * keys. If such segments are found, then the parameter and that segment will be recreated for every possible
     * site access of the installation and added to the original list of keys.
     *
     * @param array $keysToBeProcessed Array of (string) keys of parameters.
     *
     * @return array Returns the new list of parameter keys (including the potential site access versions).
     */
    public function replacePotentialSiteAccessParts (array $keysToBeProcessed)
    {
        $changedKeys = $keysToBeProcessed;

        foreach ($keysToBeProcessed as $parameterKey) {

            $keySegments = explode(".",$parameterKey);

            foreach ($keySegments as $keySegment) {
                if (in_array($keySegment, $this->allSiteAccesses)) {
                    $indexOfSegment = array_search($keySegment,$keySegments);

                    foreach ($this->allSiteAccesses as $siteAccess) {
                        if ($siteAccess !== $keySegment) {
                            $keySegments[$indexOfSegment] = $siteAccess;
                            $changedKeys[] = join(".",$keySegments);
                        }
                    }

                    break;
                }
            }
        }

        return $changedKeys;
    }

    /**
     * Takes a list of already fully formed custom parameters (without values) and cuts all possibly site access
     * dependent parameters out of that list in order to process them separately.
     *
     * <br>This separate processing comes in the form that it determines the values for those possibly site access
     * dependent parameters by looking at the site access set for the parameter, but also "default", "global", etc. which
     * are present in that site access hierarchy, to determine the value that is actually set under the circumstances
     * of that site access for the parameter.
     *
     * <br>Example: When searching for test.admin.parameter, there might not be a value for site access admin, then it could
     * be set under default, global, admin_group or any other site access from that hierarchy and so the value of
     * the highest site access in that hierarchy is determined (global before any other, then the group and lastly default)
     *
     * @param array $parametersToBeProcessed Associative, hierarchical array of parameter keys for which to determine the values.
     *
     * @return array Returns the resulting parameter array after the separate site access processing operation.
     *
     * @throws Exception Throws an exception, when trying to filter without any site accesses to filter for.
     */
    public function scanAndEditForSiteAccessDependency (array $parametersToBeProcessed)
    {
        if (count($this->currentActiveSiteAccessList) === 0) {
            throw new Exception("Cannot filter parameter list for site accesses, because no site accesses to filter for have been given.");
        }

        // First determine which of the given parameters might be site access dependent and store them in a separate array.
        $possiblySiteAccessDependentParameters =
            $this->getAllPossiblySiteAccessDependentParameters($parametersToBeProcessed);

        // Take those parameters and add them back into the overall parameters array after the processing is done.
        $parametersToBeProcessed = $this->addSiteAccessParametersBackIntoStructure(
            $possiblySiteAccessDependentParameters,
            $parametersToBeProcessed
        );

        // Return the resulting full parameters array.
        return $parametersToBeProcessed;
    }

    /**
     * Determines all site accesses and groups that have been set and are active in the installation and assembles a
     * list of these accesses to be used in processing afterwards. This list also features the order in which the
     * site accesses are looked at, with the first item being of the lowest relevance.
     */
    private function constructListOfAllSiteAccesses ()
    {
        $this->allSiteAccesses[] = "default";

        // Get the site access groups.
        if ($this->symContainer->hasParameter("ezpublish.siteaccess.groups")) {
            $groups = $this->symContainer->getParameter("ezpublish.siteaccess.groups");
            $groups = array_keys($groups);

            array_push($this->allSiteAccesses, ...$groups);
        }

        // Get the site access parameters.
        if ($this->symContainer->hasParameter("ezpublish.siteaccess.list")) {
            array_push(
                $this->allSiteAccesses,
                ...$this->symContainer->getParameter("ezpublish.siteaccess.list")
            );
        }

        $this->allSiteAccesses[] = "global";
    }

    /**
     * Gets a parameter out of a given array of parameters by looking at an array of hierarchical key segments, leading
     * to the parameter that is supposed to be taken. But it incorporates checks along the way for whether the
     * segments exist or are empty.
     *
     * <br>Example: [first,second,third] segment as keylist will be used for the given parameters, leading to
     * parameters[first][second][third] to be returned.
     *
     * @param array $keyParts The list of key segments to go through.
     * @param array $processedParameters An associative array of parameters to check with the list of keys.
     * @param false $withinCustomArray An optional boolean, which is employed by the function, when it is recursively called.
     *
     * @return array Returns the found array after following the keylist. Returns an empty array, when the keys couldn't be found or the found parameter is empty.
     */
    private function getParameterThroughParts (array $keyParts, array $processedParameters, $withinCustomArray = false)
    {
        $customParametersSoFar = [];

        if (count($keyParts) > 0) {
            if (key_exists($keyParts[0], $processedParameters)) {
                $key = $keyParts[0];
                array_splice($keyParts,0,1);
                $customParametersSoFar[$key] =
                    self::getParameterThroughParts($keyParts,$processedParameters[$key], true);

                if (count($customParametersSoFar[$key]) === 0) {
                    unset($customParametersSoFar[$key]);
                }
            }
        } else if ($withinCustomArray) {
            return $processedParameters;
        }

        return $customParametersSoFar;
    }

    /**
     * Takes a list of site access parameters and parameters to add these to and determines both the site access
     * version of the parameters that is valid for the current context (dismisses the rest) and adds these into
     * the given "comparisonParameters".
     *
     * <br>Example: if 'test.admin.parameter = "test"' and 'test.global.parameter = "not test"' exist in the list of parameters
     * to add, then 'test.admin.parameter' will be dismissed and 'test.global.parameter' will be added to the comparisonParameters,
     * since global is positioned higher in the site access hierarchie.
     *
     * @param array $parameters The array of site access parameters to add to the given list.
     * @param array $comparisonParameters The given list of parameters to add the given site access parameters to.
     *
     * @return array Returns an array which includes the added site access parameters and the previous, existing parameters.
     */
    private function addSiteAccessParametersBackIntoStructure (array $parameters, array $comparisonParameters)
    {
        $indexOfCurrentHightestAccess = 0;
        foreach ($parameters as $parameterKey => $parameterValue) {
            if (
                !in_array($parameterKey, $this->allSiteAccesses) &&
                is_array($parameterValue) &&
                key_exists($parameterKey, $comparisonParameters)
            ) {
                $comparisonParameters[$parameterKey] = $this->addSiteAccessParametersBackIntoStructure(
                    $parameterValue,
                    $comparisonParameters[$parameterKey]
                );
            } else if (
                in_array($parameterKey, $this->allSiteAccesses) &&
                is_array($parameterValue)
            ) {
                unset($comparisonParameters[$parameterKey]);
                $currentAccessIndex = array_search($parameterKey, $this->allSiteAccesses);

                if (!in_array($parameterKey, $this->currentActiveSiteAccessList)) {
                    unset($parameters[$parameterKey]);
                    continue;
                }

                $indexOfCurrentHightestAccess =
                    ($currentAccessIndex < $indexOfCurrentHightestAccess)? $indexOfCurrentHightestAccess : $currentAccessIndex;
                $results = $this->buildFullParameterKeys($parameterValue);

                foreach ($results as $resultKey => $resultValue) {
                    if (
                        $currentAccessIndex < $indexOfCurrentHightestAccess &&
                        key_exists($resultKey,$comparisonParameters)
                    ) {
                        continue;
                    }
                    $comparisonParameters[$resultKey] = $resultValue;
                }
            }
        }

        return $comparisonParameters;
    }

    /**
     * Takes an associative, hierarchical array of parameters and assembles the keys of the different levels down
     * to the actual value (of the parameter) to complete keys for every parameter.
     *
     * @param array $parameters Associative, hierarchical array of parameters.
     * @param string $predecessorKeys An optional key which states what key came in the level above the current.
     *
     * @return array Returns an array of fully assembled parameter keys (if it was possible to do so), returns an empty array if no keys could be assembled.
     */
    private function buildFullParameterKeys (array $parameters, $predecessorKeys = null)
    {
        $result = [];

        foreach ($parameters as $parameterKey => $parameterValue) {
            if (
                $parameterKey === "parameter_value" ||
                !is_array($parameterValue)
            ) {
                if ($predecessorKeys) {
                    $result[$predecessorKeys][$parameterKey] = $parameterValue;
                } else {
                    $result[$parameterKey] = $parameterValue;
                }
                continue;
            }

            $tmpResult = $this->buildFullParameterKeys(
                $parameterValue,
                $predecessorKeys?
                    $predecessorKeys.".".$parameterKey : $parameterKey
            );

            foreach ($tmpResult as $tmpResultKey => $tmpResultValue) {
                $result[$tmpResultKey] = $tmpResultValue;
            }
        }

        return $result;
    }

    /**
     * Checks the given parameters array for any keys and by extension parameters which feature one a
     * site access key segment and collects these parameters in a separate array.
     *
     * @param array $parametersToBeProcessed Associative, hierarchical array of parameters to search for site access dependencies.
     *
     * @return array Returns an array of site access dependent parameters, which have been found in the given ones or an empty array if non could be found.
     */
    private function getAllPossiblySiteAccessDependentParameters (array $parametersToBeProcessed)
    {
        $result = [];

        foreach ($parametersToBeProcessed as $parameterKey => $parameterValue) {
            if (!is_array($parameterValue) || $parameterKey === "parameter_value") {
                return [];
            } else if (in_array($parameterKey,$this->allSiteAccesses)) {
                $result[$parameterKey] = $parametersToBeProcessed[$parameterKey];
                unset($parametersToBeProcessed[$parameterKey]);
            } else {
                $tmpResult = $this->getAllPossiblySiteAccessDependentParameters($parameterValue);

                if (count($tmpResult) > 0) {
                    $result[$parameterKey] = [];
                }

                foreach (array_keys($tmpResult) as $siteAccess) {
                    if (key_exists($siteAccess,$result[$parameterKey])) {
                        $result[$parameterKey][$siteAccess] =
                            $this->addInKeysUnderSameSiteAccess($result, $tmpResult[$siteAccess]);
                    } else {
                        $result[$parameterKey][$siteAccess] = $tmpResult[$siteAccess];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Takes a list of parameters to add to an existing associative array and goes down the keys of both arrays to
     * find a key of a parameter which is missing in the list to add to. If those keys are found, the content
     * of the parameter will be added at that point in the list to add to.
     *
     * <br>Functions similarly to array_replace_recurive of php.
     *
     * @param array $listToAddInto An associative array to add the parameter to.
     * @param array $parametersToAdd Associative array of parameters to add into the given list.
     *
     * @return array Returns the list to which the parameters have been added.
     */
    private function addInKeysUnderSameSiteAccess (array $listToAddInto, array $parametersToAdd)
    {
        foreach($parametersToAdd as $parameterKey => $parameterValue) {
            if (
                key_exists($parameterKey,$listToAddInto) &&
                is_array($listToAddInto[$parameterKey]) &&
                is_array($parameterValue)
            ) {
                $listToAddInto[$parameterKey] = $this->addInKeysUnderSameSiteAccess(
                    $listToAddInto[$parameterKey],
                    $parametersToAdd
                );
            } else {
                $listToAddInto[$parameterKey] = $parameterValue;
            }
        }

        return $listToAddInto;
    }
}
