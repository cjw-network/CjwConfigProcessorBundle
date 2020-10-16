<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
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

        return $this->render("@CJWConfigProcessor/line/param_view.html.twig", ["parameterList" => $parameters]);
    }

    public function getCurrentSAParameters () {
        $saParameters = ConfigProcessCoordinator::getSiteAccessParameters();

        return $this->render("@CJWConfigProcessor/line/param_view_current_sa.html.twig", ["siteAccessParameters" => $saParameters]);
    }

    public function getSpecificSAParameters (string $siteAccess) {
        try {
            $specSAParameters = ConfigProcessCoordinator::getParametersForSpecificSiteAccess($siteAccess);
        } catch (Exception $error) {
            $specSAParameters = [];
        }

        return $this->render("@CJWConfigProcessor/line/param_view_specific_sa.html.twig", ["specificSiteAccessParameters" => $specSAParameters]);
    }
}
