<?php


namespace App\CJW\ConfigProcessorBundle\src\ConfigProcessorBundle;


use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomParamProcessor
{
    /** @var ContainerInterface */
    private $symContainer;
    /** @var array */
    private $currentActiveSiteAccessList;
    /** @var array */
    private $allSiteAccesses;

    public function __construct(
        ContainerInterface $symContainer = null,
        array $siteAccessList = []
    ) {
        $this->symContainer = $symContainer;
        $this->currentActiveSiteAccessList = $siteAccessList;

        if ($this->symContainer) {
            $this->constructListOfAllSiteAccesses();
        }
    }


    public function getCustomParameters (
        array $customParameterKeys,
        array $processedParameters
    ): array {
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

    public function replacePotentialSiteAccessParts (array $keysToBeProcessed): array {
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


    public function scanAndEditForSiteAccessDependency (array $parametersToBeProcessed): array {
        $possiblySiteAccessDependentParameters =
            $this->getAllPossiblySiteAccessDependentParameters($parametersToBeProcessed);

        $parametersToBeProcessed = $this->addSiteAccessParametersBackIntoStructure(
            $possiblySiteAccessDependentParameters,
            $parametersToBeProcessed
        );

        return $parametersToBeProcessed;
    }

    private function constructListOfAllSiteAccesses (): void {
        $this->allSiteAccesses[] = "default";

        if ($this->symContainer->hasParameter("ezpublish.siteaccess.groups")) {
            $groups = $this->symContainer->getParameter("ezpublish.siteaccess.groups");
            $groups = array_keys($groups);

            array_push($this->allSiteAccesses, ...$groups);
        }

        if ($this->symContainer->hasParameter("ezpublish.siteaccess.list")) {
            array_push(
                $this->allSiteAccesses,
                ...$this->symContainer->getParameter("ezpublish.siteaccess.list")
            );
        }

        $this->allSiteAccesses[] = "global";
    }

    private function getParameterThroughParts (
        array $keyParts,
        array $processedParameters,
        bool $withinCustomArray = false
    ): array {
        $customParametersSoFar = [];

        if (count($keyParts) > 0) {
            if (key_exists($keyParts[0], $processedParameters)) {
                $key = $keyParts[0];
                array_splice($keyParts,0,1);
                $customParametersSoFar[$key] = self::getParameterThroughParts($keyParts,$processedParameters[$key], true);
            }
        } else if ($withinCustomArray) {
            return $processedParameters;
        }

        return $customParametersSoFar;
    }

    private function addSiteAccessParametersBackIntoStructure (
        array $parameters,
        array $comparisonParameters
    ): array {
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

                if (
                    !in_array($parameterKey, $this->currentActiveSiteAccessList)
                ) {
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

    private function buildFullParameterKeys (
        array $parameters,
        string $predecessorKeys = null
    ): array {
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

    private function getAllPossiblySiteAccessDependentParameters (array $parametersToBeProcessed): array {
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

    private function addInKeysUnderSameSiteAccess (array $listToAddInto, array $parametersToAdd): array {
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
