<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * Class ParameterAccessBag is a small class which is only used to get the entirety of all stored parameters from
 * a FronzenParameterBag.
 *
 * @package CJW\CJWConfigProcessor\src\ConfigProcessorBundle
 */
class ParameterAccessBag extends FrozenParameterBag
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container->getParameterBag()->parameters);
    }

    /**
     * Returns all parameters of the parameter bag.
     *
     * @return array Returns the array as it is stored in the original parameter bag.
     */
    public function getParameters()
    {
        // The "$this->parameters" attribute stems from the parent class.
        return $this->parameters;
    }

}
