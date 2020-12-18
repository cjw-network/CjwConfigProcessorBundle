<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


/**
 * Class ConfigProcessor serves to actually transform the internal parameter list of Symfony into a more readable and
 * searchable array of parameters.
 *
 * @package CJW\CJWConfigProcessor\ConfigProcessorBundle\src
 */
class ConfigProcessor
{
    /**
     * @var array Stores all the processed parameters with their namespaces as keys in the array.
     */
    private $processedParameters;

    public function __construct()
    {
        $this->processedParameters = array();
    }

    /**
     * @return array Returns the internal Object-based Parameterlist
     */
    public function getProcessedParameters()
    {
        return $this->processedParameters;
    }

    /**
     * Function to parse all the parameters of the symfony service container in order to reformat them into a more
     * readable structure.
     *
     * @param array $parameters A list of given parameters to be processed and reformatted.
     *
     * @return array Returns the processed parameters in the form of an associative array.
     */
    public function processParameters(array $parameters)
    {
        if ($parameters && is_array($parameters)) {
            $keys = array_keys($parameters);

            foreach ($keys as $key) {
                $namespaceAndRest = $this->parseIntoParts($key);
                $parameterValue = $parameters[$key];

                // check whether the parameter key (namespace) already exists in the application
                if(!isset($this->processedParameters[$namespaceAndRest[0]])) {
                    $this->processedParameters[$namespaceAndRest[0]] = new ProcessedParamModel($namespaceAndRest[0]);
                }

                $this->processedParameters[$namespaceAndRest[0]]->addParameter($namespaceAndRest, (array) $parameterValue);

            }
        }

        return $this->reformatParametersForOutput();
    }

    /**
     * Takes a given key and splits it into the different segments that are present in it
     * (namespace, (with eZ) siteaccess, actual parameter, etc).
     *
     * @param string $key The parameter key to be split into key segments.
     *
     * @return array | false Returns an array of key segments or false, if it the given key could not be split.
     */
    private function parseIntoParts ($key)
    {
        if ($key && strlen($key) > 0) {
            $splitStringCarrier = explode(".",$key);

            if ($splitStringCarrier) {
                return $splitStringCarrier;
            }

            return $key;
        }
        return false;
    }


    /**
     * Turns the array of ProcessedParamModel-Objects into an associative array with the keys and the values attached to them.
     * Also sorts the keys of the array alphabetically so they are more easily searchable.
     */
    private function reformatParametersForOutput()
    {
        $formattedOutput = [];
        foreach($this->processedParameters as $parameter) {
            $formattedOutput[$parameter->getKey()] = $parameter->reformatForOutput();
        }

        return $this->sortParameterKeys($formattedOutput);
    }

    /**
     * Function to sort the keys of a given, associative array alphabetically.
     *
     * @param array $parameters The associative array of parameters to be sorted.
     *
     * @return array Returns the sorted array.
     */
    private function sortParameterKeys (array $parameters)
    {
        ksort($parameters, SORT_STRING);

        foreach ($parameters as $key => $value) {
            if (is_array($parameters[$key])) {
                $parameters[$key] = $this->sortParameterKeys($parameters[$key]);
            }
        }

        return $parameters;
    }
}
