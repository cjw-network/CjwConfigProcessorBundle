<?php


namespace App\CJW\ConfigProcessorBundle;


use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

class ParameterAccessBag extends FrozenParameterBag
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container->getParameterBag()->parameters);
    }

    /**
     * Returns the parameters of the parameter bag.
     *
     * @return array Returns the array as it is stored in the original parameter bag.
     */
    public function getParameters() {
        // The "$this->parameters" attribute stems from the parent class.
        return $this->parameters;
    }

}
