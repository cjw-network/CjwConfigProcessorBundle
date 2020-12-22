<?php


namespace CJW\CJWConfigProcessor\Controller;


use CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle\LocationRetrievalCoordinator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ConfigProcessLocationInfoController is a controller designed to enable frontend integration with getting locations
 * for specific parameters.
 *
 * @package CJW\CJWConfigProcessor\Controller
 */
class ConfigProcessLocationInfoController extends AbstractController
{

    private $projectDir;

    /**
     * ConfigProcessLocationInfoController constructor.
     * @param ContainerInterface $symContainer
     * @param string $projectDir
     */
    public function __construct(ContainerInterface $symContainer, $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->container = $symContainer;
        LocationRetrievalCoordinator::initializeCoordinator();
    }

    /**
     * Responsible for determining the location info for a given parameter.
     *
     * @param string $parameter The key to the parameter to retrieve locations for.
     * @param string $withSiteAccess String representation of a boolean to determine, whether to view the parameter key in a site access context.
     *
     * @return JsonResponse A json response which includes the paths where the parameter has been found and the values attached to these paths (also site access if set to true).
     */
    public function retrieveLocationsForParameter ($parameter, $withSiteAccess): JsonResponse
    {
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
                if ($location !== "siteaccess-origin" && strpos($location,$this->projectDir) > -1) {
                    $newKey = substr($location,strlen($this->projectDir));

                    $locations[$newKey] = $value;
                    unset($locations[$location]);
                }
            }
        }

        return $this->json($locations);
    }
}
