<?php


namespace App\CJW\ConfigProcessorBundle\src;


class ProcessedParamModel
{

    /**
     * Stores the key (the namespace) the parameters belong to.
     * @var string
     */
    private $key;

    /**
     * Stores the corresponding parameters of the namespace in an array.
     * @var array
     */
    private $parameters;

    public function __construct(string $key)
    {
        $this->key = $key;
        $this->parameters = array();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Takes a given parameter and adds it to the parameter list in the object.
     * @param array $keys
     * @param array $valueArray
     */
    public function addParameter(array $keys,array $valueArray = []) {
        $modelToAddTo = $this->constructByKeys($keys);

        // Is there anything to add? If so, is it just one value or an entire array?
        if(count($valueArray) > 0) {
            array_push($modelToAddTo->parameters, $valueArray);
        }
    }

    /**
     * Recursive function to go through all gathered parameters and reformat the internal values as well as the keys
     * into an associative array.
     *
     * @return array
     */
    public function reformatForOutput() {
        $outputArray = [];
        foreach($this->parameters as $parameter) {

            if (!$parameter instanceof ProcessedParamModel) {
                return $parameter;
            }
            $outputArray[$parameter->getKey()] = $parameter->reformatForOutput();
        }
        return $outputArray;
    }

    /**
     * Takes an array of keys and processes them. Constructs a sort of tree based on the keys.
     * If all keys already exist in its list, nothing will be done to the objects.
     *
     * @param array $keys Given list of keys after which to construct the "key-list" tree-like structure in the parameters.
     * @returns ProcessedParamModel Returns the model where the last key of the given list is stored.
     */
    private function constructByKeys(array $keys) {
        $paramCarrier = $this->determineIfKeyIsPresent($keys, 1);
        $foundOnLevel = array_search($paramCarrier->key,$keys);

        if ($foundOnLevel >= 0) {
            for ($i = $foundOnLevel+1; $i < count($keys); ++$i) {
                $paramCarrier = $paramCarrier->addParamModel(new ProcessedParamModel($keys[$i]));
            }
        }

        return $paramCarrier;
    }

    /**
     * Determines whether keys from a given array are present in the parameters of the object the function is started in.
     * Goes through the children recursively and returns the object where the last key was found in order to allow further operations to
     * be performed on the object.
     *
     * @param array $keys Given array of keys that will be searched for in the parameters of the object.
     * @param int $level Number that states what key to search for in the array in the next run of the function.
     * @return $this Returns the object the function has last been called unsuccessfully on. That means the object where the last key was found is returned.
     */
    private function determineIfKeyIsPresent(array $keys, int $level)
    {
        if ($level < count($keys)) {
            foreach ($this->parameters as $entry) {
                if ($entry->getKey() === $keys[$level]) {
                    return $entry->determineIfKeyIsPresent($keys, $level+1);
                }
            }
        }

        return $this;
    }

    /**
     * Private function of the model which adds an entire new ProcessedParamModel object into the parameter list.
     *
     * @param ProcessedParamModel $paramModel The model to add to the parameters (typically means there have been more keys in the given key list then are present in the existing parameters.
     * @return ProcessedParamModel Returns itself in order to allow further operations on itself.
     */
    private function addParamModel(ProcessedParamModel $paramModel) {
        array_push($this->parameters,$paramModel);

        return $paramModel;
    }
}
