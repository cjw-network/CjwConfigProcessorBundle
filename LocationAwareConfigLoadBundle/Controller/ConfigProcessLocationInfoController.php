<?php


namespace App\CJW\LocationAwareConfigLoadBundle\Controller;


use App\CJW\LocationAwareConfigLoadBundle\src\LocationRetrievalCoordinator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ConfigProcessLocationInfoController extends AbstractController
{

    private $projectDir;

    public function __construct(ContainerInterface $symContainer, string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->container = $symContainer;
        LocationRetrievalCoordinator::initializeCoordinator();
    }

    public function retrieveLocationsForParameter (string $parameter, string $withSiteAccess) {
        $saPresent = ($withSiteAccess && $withSiteAccess !== "false")?? false;
        $locations = LocationRetrievalCoordinator::getParameterLocations($parameter, $saPresent);

        if ($locations) {
            foreach ($locations as $location => $value) {
                if ($location !== "siteaccess") {
                    $newKey = substr($location,strlen($this->projectDir));

                    $locations[$newKey] = $value;
                    unset($locations[$location]);
                }
            }
        }

        $response = new Response(json_encode($locations));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }
}
