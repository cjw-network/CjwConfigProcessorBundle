<?php


namespace App\CJW;


use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

class TestFrozenBag extends FrozenParameterBag
{

    private $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
        parent::__construct($container->getParameterBag()->parameters);
    }

    public function repurposeParameters() {
        return $this->parameters;
    }

}
