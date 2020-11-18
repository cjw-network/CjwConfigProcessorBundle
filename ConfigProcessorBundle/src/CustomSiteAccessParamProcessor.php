<?php


namespace App\CJW\ConfigProcessorBundle\src;


use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomSiteAccessParamProcessor
{
    /** @var ContainerInterface */
    private $symContainer;
    /** @var array */
    private $parametersToBeProcessed;
    /** @var array */
    private $currentActiveSiteAccessList;
    /** @var array */
    private $allSiteAccesses;

    public function __construct(ContainerInterface $symContainer,array $parametersToBeProcessed, array $siteAccessList) {
        $this->symContainer = $symContainer;
        $this->parametersToBeProcessed = $parametersToBeProcessed;
        $this->currentActiveSiteAccessList = $siteAccessList;
        $this->allSiteAccesses = [];
        $this->constructListOfAllSiteAccesses();
    }

    public function processGivenParameters () {
        $possiblySiteAccessDependentParameters =
            $this->getAllPossiblySiteAccessDependentParameters($this->parametersToBeProcessed);

        $this->parametersToBeProcessed = $this->addSiteAccessParametersBackIntoStructure(
            $possiblySiteAccessDependentParameters,
            $this->parametersToBeProcessed
        );

        return $this->parametersToBeProcessed;
    }

    private function addSiteAccessParametersBackIntoStructure (array $parameters, array $comparisonParameters) {
        foreach ($parameters as $parameterKey => $parameterValue) {
            if (!in_array($parameterKey, $this->allSiteAccesses) && is_array($parameterValue)) {
                $comparisonParameters[$parameterKey] = $this->addSiteAccessParametersBackIntoStructure(
                    $parameterValue,
                    $comparisonParameters[$parameterKey]
                );
            } else if (
                in_array($parameterKey, $this->allSiteAccesses) &&
                is_array($parameterValue)
            ) {
                if (!in_array($parameterKey, $this->currentActiveSiteAccessList)) {
                    unset($parameters[$parameterKey]);
                    continue;
                }

                unset($comparisonParameters[$parameterKey]);
                $results = $this->buildFullParameterKeys($parameterValue);

                foreach ($results as $resultKey => $resultValue) {
                    $comparisonParameters[$resultKey] = $resultValue;
                }
            }
        }

        return $comparisonParameters;
    }

    private function buildFullParameterKeys (array $parameters, string $predecessorKeys = null) {
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

            $tmpResult = $this->buildFullParameterKeys($parameterValue, $predecessorKeys? $predecessorKeys.".".$parameterKey : $parameterKey);
            foreach ($tmpResult as $tmpResultKey => $tmpResultValue) {
                $result[$tmpResultKey] = $tmpResultValue;
            }
        }

        return $result;
    }

    private function constructListOfAllSiteAccesses () {
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

    private function getAllPossiblySiteAccessDependentParameters (array $parametersToBeProcessed) {
        $result = [];

        foreach ($parametersToBeProcessed as $parameterKey => $parameterValue) {
            if (!is_array($parameterValue) || $parameterKey === "parameter_value") {
                return [];
            } else if (in_array($parameterKey,$this->allSiteAccesses)) {
                $result[$parameterKey] = $parametersToBeProcessed[$parameterKey];
                unset($parametersToBeProcessed[$parameterKey]);
            } else if (count($result) === 0) {
                $tmpResult = $this->getAllPossiblySiteAccessDependentParameters($parameterValue);
                $result[$parameterKey] = [];

                foreach (array_keys($tmpResult) as $siteAccess) {
                    if (key_exists($siteAccess,$result)) {
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

    private function addInKeysUnderSameSiteAccess (array $listToAddInto, array $parametersToAdd) {
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
