<?php


namespace App\CJW\LocationAwareConfigLoadBundle\Controller;


use App\CJW\LocationAwareConfigLoadBundle\src\LocationRetrievalCoordinator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ConfigProcessLocationInfoController extends AbstractController
{

    public function __construct(ContainerInterface $symContainer)
    {
        $this->container = $symContainer;
        LocationRetrievalCoordinator::initializeCoordinator();
    }

    public function retrieveLocationsForParameter (string $parameter) {
        $locations = LocationRetrievalCoordinator::getParameterLocations($parameter);

        $response = new Response(json_encode($locations));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }
}
