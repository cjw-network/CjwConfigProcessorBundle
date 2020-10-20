<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigProcessController extends AbstractController
{
    public function __construct (
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    )
    {
        $this->container = $symContainer;
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
    }

    public function getStartPage () {
        ConfigProcessCoordinator::startProcess();

        return $this->render("@CJWConfigProcessor/index.html.twig");
    }

    public function getParameterList () {
        $parameters = ConfigProcessCoordinator::getProcessedParameters();

        return $this->render("@CJWConfigProcessor/full/param_view.html.twig", ["parameterList" => $parameters]);
    }

    public function siteAccessSelection () {
        $availableSiteAccesses = ConfigProcessCoordinator::getSiteAccessListForController();

        return $this->render(
            "@CJWConfigProcessor/full/site_access_selection.html.twig",
            [
                "siteAccesses" => $availableSiteAccesses
            ]
        );
    }

    public function getCurrentSAParameters () {
        $saParameters = ConfigProcessCoordinator::getSiteAccessParameters();

        return $this->render("@CJWConfigProcessor/full/param_view_siteaccess.html.twig", ["siteAccessParameters" => $saParameters]);
    }

    public function getSpecificSAParameters (string $siteAccess) {
        try {
            $specSAParameters = ConfigProcessCoordinator::getParametersForSpecificSiteAccess($siteAccess);
        } catch (Exception $error) {
            $specSAParameters = [];
        }

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess.html.twig",
            [
                "siteAccess" => $siteAccess,
                "siteAccessParameters" => $specSAParameters
            ]
        );
    }

    public function compareSiteAccesses (string $firstSiteAccess, string $secondSiteAccess) {
        $firstSiteAccessParameters = [];
        $secondSiteAccessParameters = [];

        try {
            $firstSiteAccessParameters = ConfigProcessCoordinator::getParametersForSpecificSiteAccess($firstSiteAccess);
            $secondSiteAccessParameters = ConfigProcessCoordinator::getParametersForSpecificSiteAccess($secondSiteAccess);
        } catch (Exception $error) {
            $firstSiteAccessParameters = (count($firstSiteAccessParameters) > 0)? $firstSiteAccessParameters : [];
            $secondSiteAccessParameters = (count($secondSiteAccessParameters) > 0)? $secondSiteAccessParameters : [];
        }

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess_compare.html.twig",
            [
                "firstSiteAccess" => $firstSiteAccess,
                "secondSiteAccess" => $secondSiteAccess,
                "firstSiteAccessParameters" => $firstSiteAccessParameters,
                "secondSiteAccessParameters" => $secondSiteAccessParameters
            ]
        );
    }
}
