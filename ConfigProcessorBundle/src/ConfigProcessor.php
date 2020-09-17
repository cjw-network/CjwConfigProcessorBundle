<?php


namespace App\CJW\ConfigProcessorBundle\src;


class ConfigProcessor
{
    /**
     * Stores all the processed parameters with their namespaces as keys in the array.
     * @var array
     */
    private $processedParameters;

    public function __construct()
    {
        $this->processedParameters = array();
    }

    /**
     * @return array
     */
    public function getProcessedParameters(): array
    {
        return $this->processedParameters;
    }

    /**
     * Function to parse all the parameters of the symfony service container in order to reformat them into a more
     * readable structure.
     *
     * @param array $parameters A list of given parameters to be processed and reformatted.
     * @returns array Returns an array of the processed and formatted parameters.
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
     * (namespace, (with eZ) siteaccess, actual parameter etc).
     *
     * @param string $key
     * @return array | false
     */
    private function parseIntoParts (string $key) {
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
     */
    private function reformatParametersForOutput() {
        $formattedOutput = [];
        foreach($this->processedParameters as $parameter) {
            $formattedOutput[$parameter->getKey()] = $parameter->reformatForOutput();
        }
        ksort($formattedOutput,SORT_STRING);
        return $formattedOutput;
    }
}
