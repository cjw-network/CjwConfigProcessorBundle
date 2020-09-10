<?php


namespace App\CJW\ConfigProcessorBundle\src;


use App\CJW\ConfigProcessorBundle\src\ProcessedParamModel;
use InvalidArgumentException;

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

//
//    /**
//     * Takes an array of given parameters and processes them.
//     * @param array $parameters
//     * @return array
//     */
//    public function processParameters(array $parameters) {
//        if ($parameters && is_array($parameters)) {
//            $keys = array_keys($parameters);
//            foreach ($parameters as $parameter) {
//                $namespace = $this->parameterParser($parameter);
//                if ($namespace) {
//                    try {
//                        $this->processedParameters[$namespace];
//                    } catch (\Exception $error) {
//                        $this->processedParameters[$namespace] = new ProcessedParamModel($namespace);
//                    } finally {
////                        $this->processedParameters[$namespace]->addParameter(array_splice($parameter, 1));
//                        $paramExploded = explode(".",$parameter);
//                        $this->processedParameters[$namespace]->addParameter(array_splice($paramExploded, 1));
//                    }
////                    if (!$this->processedParameters[$namespace]) {
////                        $this->processedParameters[$namespace] = new ProcessedParamModel($namespace);
////                    }
////
////                    $this->processedParameters[$namespace]->addParameter(array_splice($parameter, 1));
//                }
//            }
//        } else {
//            throw new InvalidArgumentException("The given parameters are not of type array and thus can not be processed by the function.");
//        }
//
//        return $this->processedParameters;
//    }

    /**
     * Function to parse all the parameters of the symfony service container in order to reformat them into a more
     * readable structure.
     *
     * @param array $parameters A list of given parameters to be processed and reformatted.
     * @returns array Returns an array of the processed and formatted parameters.
     */
    public function processParameters(array $parameters)
    {
        if ($parameters && is_array($parameters)) {
            $keys = array_keys($parameters);

            foreach ($keys as $key) {
                $namespaceAndRest = $this->parseIntoParts($key);
                $parameterValue = $parameters[$key];

                try {
                    $this->processedParameters[$namespaceAndRest[0]];
                } catch (\Exception $error) {
                    $this->processedParameters[$namespaceAndRest[0]] = new ProcessedParamModel($namespaceAndRest[0]);
                } finally {
                    $this->processedParameters[$namespaceAndRest[0]]->addParameter($namespaceAndRest, (array) $parameterValue);
                }
            }
        }

        return $this->processedParameters;
    }

    /**
     * Takes a given parameter and processes it to determine the key / namespace attached to that parameter.
     * @param string $parameter
     * @return array | false
     */
    private function parseIntoParts (string $parameter) {
        if ($parameter && strlen($parameter) > 0) {
            $splitStringCarrier = explode(".",$parameter);

            if ($splitStringCarrier) {
                return $splitStringCarrier;
            }

            return $parameter;
        }
        return false;
    }

//    /**
//     * Takes a given parameter and processes it to determine the key / namespace attached to that parameter.
//     * @param string $parameter
//     * @return string | false
//     */
//    private function parseForNamespace (string $parameter) {
//        if ($parameter && strlen($parameter) > 0) {
//            $splitStringCarrier = explode(".",$parameter);
//
//            return $splitStringCarrier[0];
//        }
//
//        return false;
//    }
}
