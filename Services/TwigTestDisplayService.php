<?php


namespace CJW\CJWConfigProcessor\Services;


use CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle\LocationRetrievalCoordinator;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Class TwigTestDisplayService is responsible for bringing the paths found for every parameter to twig templates.
 *
 * @package CJW\CJWLocationAwareConfigLoadBundle\Services
 */
class TwigTestDisplayService extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var array An array which not only stores the parameters, but also the paths they have been read from (including the values set there)
     */
    public $parametersAndLocations;

    public function __construct()
    {
       $this->parametersAndLocations = LocationRetrievalCoordinator::getParametersAndLocations();
    }

    /**
     * Function to return global variables to be used in twig templates
     */
    public function getGlobals()
    {
        return [
            "cjw_param_location" => $this->parametersAndLocations?? [],
        ];
    }
}
