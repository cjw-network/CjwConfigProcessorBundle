<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

/**
 * Class LocationAwareParameterBag is a custom parameter bag which is designed to keep track of the resources being
 * loaded into the container and this bag. It is reliant on information it receives from other classes of the load
 * process higher up in the chain to keep its path information up to date.
 *
 * @package CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle
 */
class LocationAwareParameterBag extends EnvPlaceholderParameterBag
{

    /**
     * @var string Stores the current location that is being loaded.
     */
    private $currentLocation;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        // The starting point in the loading process is typically the kernel
        $this->currentLocation = "kernel";
    }

    /**
     * A function for the parameterbag to keep track of the path that is currently being loaded.
     * It depends on external intervention in order to be held up to date and used at all.
     * It has to be used either prior to the parameters being given to the parameterbag or just as they are given.
     * In any other case, the location that has been set prior will be used instead!
     *
     * @param string $location The path / file that is being loaded.
     */
    public function setCurrentLocation($location)
    {
        $this->currentLocation = $location;
    }

    /**
     * @override
     * This is an override of the set function which is typically used to add parameters to the bag.
     * But since it is important to keep track of the parameters's origin too, the function has been edited in
     * order to set the parameter, its value and the path the value stems from at the same time.
     *
     * @param string $name The name of the parameter.
     * @param mixed $value The value being set to the parameter.
     */
    public function set($name, $value)
    {
        // Give the parameter, the value and the current location
        CustomValueStorage::addParameterOrLocation($name,$value,$this->currentLocation);

        // Continue with standard parameter setting procedure
        parent::set($name, $value);
    }
}
