<?php


namespace App\CJW\ConfigProcessorBundle\src;


class ProcessedParamModel implements \Serializable
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
     *
     * @param array $keys A list of keys which describe the path through the data structure and to which node to add the values
     * @param array $valueArray A list of values the final node will be passed as parameters.
     */
    public function addParameter(array $keys,array $valueArray = []) {
        $modelToAddTo = $this->constructByKeys($keys);

        // Is there anything to add?
        if(count($valueArray) > 0) {
            array_push($modelToAddTo->parameters, $valueArray);
        }
    }

    /**
     * Recursive function to go through all gathered parameters and reformat the internal values as well as the keys
     * into an associative array.
     *
     * @return array Returns an array that contains all parameters and their values
     */
    public function reformatForOutput() {
        $outputArray = [];
        foreach($this->parameters as $parameter) {

            // If there is no more Object as a parameter, then the end of the "branch" has been reached and the actual value can be returned
            if (!$parameter instanceof ProcessedParamModel) {
                return $parameter;
            }

            // Otherwise the returned value of the children (parameters) of the object are being slotted into an associative array
            $outputArray[$parameter->getKey()] = $parameter->reformatForOutput();
        }
        return $outputArray;
    }

    /**
     * Searches for a child who's key matches the siteaccess if one is found then it will be returned. In any
     * other case false is given back.
     *
     * @param string $siteaccess The access to search for in the key.
     * @return ProcessedParamModel|false Returns either the object that matches or false if nothing matches.
     */
    public function filterForSiteAccess(string $siteaccess) {
        foreach ($this->parameters as $parameter) {
            if ($parameter instanceof ProcessedParamModel && $parameter->getKey() === $siteaccess) {
                return $parameter;
            }
        }

        return false;
    }

    /**
     * Recursively looks through the parameters of the model and tries to find the model with the given key.
     * When found, the model will be removed and returned. If not, false is returned.
     *
     * @param string $key The key
     * @return array | false;
     */
    public function removeParamModel(string $key) {
        for ($i = 0; $i < count($this->parameters); ++$i) {
            if ($this->parameters[$i] instanceof ProcessedParamModel) {

                if (count($this->parameters[$i]->getParameters()) === 0) {
                    array_splice($this->parameters,$i,1);
                    return false;
                }

                if ($this->parameters[$i]->getKey() === $key) {
                    return array_splice($this->parameters, $i, 1);
                }

                return $this->parameters[$i]->removeParamModel($key);
            }
        }

        return false;
    }

    /**
     * Starting from where it is called, recursively goes through every one of its parameters and adds their keys to the
     * full name of that parameter then adds every built full name into an array of
     * parameter names.
     *
     * @return array Returns an array of strings which represent the full parameter names of every one of the object's parameters.
     */
    public function getAllFullParameterNames()
    {
        $parameterNameArray = [];

        foreach ($this->parameters as $parameter) {
            if ($parameter instanceof ProcessedParamModel) {
                $restNameArray = $parameter->getAllFullParameterNames();

                foreach ($restNameArray as $restName) {
                    array_push($parameterNameArray, "$this->key.$restName");
                }
            }
        }

        // If there is nothing in the parameterNameArray (meaning that this object did not have object children / parameters) the key of this object is returned as an array
        return (count($parameterNameArray)>0)? $parameterNameArray : (array) $this->key;
    }

    /**
     * @inheritDoc
     * Used to serialize the object and provides a string that represents the
     * parameters of the object.
     */
    public function serialize()
    {
        return serialize(
            [
                $this->key,
                $this->parameters,
            ]
        );
    }

    /**
     * @inheritdoc
     * Used to deserialize the object and provide its parameters from the former
     * serialized string-version.
     */
    public function unserialize($serialized)
    {
        list(
            $this->key,
            $this->parameters,
        ) = unserialize($serialized);
    }

    /***********************************************
     *
     * Private methods of the class which are called by the public functions.
     *
     ***********************************************/

    /**
     * Takes an array of keys and processes them. Constructs a sort of tree based on the keys.
     * If all keys already exist in its list, nothing will be done to the objects.
     *
     * @param array $keys Given list of keys after which to construct the "key-list" tree-like structure in the parameters.
     * @return ProcessedParamModel Returns the model where the last key of the given list is stored.
     */
    private function constructByKeys(array $keys) {
        $paramCarrier = $this->determineIfKeyIsPresent($keys);
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
    private function determineIfKeyIsPresent(array $keys, int $level = 1)
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
