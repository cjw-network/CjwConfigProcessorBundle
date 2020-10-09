<?php


namespace App\CJW\LocationAwareConfigLoadBundle\src;


use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
//use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LocationAwareParameterBag extends EnvPlaceholderParameterBag
{

    /** @var string Stores the current location that is being loaded */
    private $currentLocation;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->currentLocation = "kernel";
    }

    /**
     * @param string $location
     */
    public function setCurrentLocation(string $location) {
        $this->currentLocation = $location;
    }

    /**
     * @override
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, $value)
    {
        CustomValueStorage::addParameterOrLocation($name,$value,$this->currentLocation);
        parent::set($name, $value);
    }

    /**
     * Serves to copy the parameter bag
     *
     * @return LocationAwareParameterBag
     */
    public function copyBag(): LocationAwareParameterBag {
        $resultBag = new LocationAwareParameterBag();

        foreach($this->parameters as $key => $value) {
            $resultBag->set($key, $value);
        }

        return $resultBag;
    }
}
