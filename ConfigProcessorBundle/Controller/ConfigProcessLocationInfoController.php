<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\LocationAwareConfigLoadBundle\LocationRetrievalCoordinator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        $group = null;

        if ($saPresent && $this->container->hasParameter("ezpublish.siteaccess.groups_by_siteaccess")) {
            $siteAccessGroups = $this->container->getParameter("ezpublish.siteaccess.groups_by_siteaccess");
            $siteAccess = explode(".",$parameter)[1];
            if ($siteAccessGroups && isset($siteAccessGroups[$siteAccess])) {
                $group = $siteAccessGroups[$siteAccess];
            }
        }

        $locations = LocationRetrievalCoordinator::getParameterLocations($parameter, $group, $saPresent);

        if ($locations) {
            foreach ($locations as $location => $value) {
                if ($location !== "siteaccess-origin") {
                    $newKey = substr($location,strlen($this->projectDir));

                    $locations[$newKey] = $value;
                    unset($locations[$location]);
                }
            }
        }

        return $this->json($locations);
    }
}
