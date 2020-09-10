<?php


namespace App\CJW\ConfigProcessorBundle;


use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

class ParameterAccessBag extends FrozenParameterBag
{

    private $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
        parent::__construct($container->getParameterBag()->parameters);
    }

    public function getParameters() {
        return $this->parameters;
    }

}
