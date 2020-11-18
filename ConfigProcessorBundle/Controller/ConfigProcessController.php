<?php


namespace App\CJW\ConfigProcessorBundle\Controller;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use App\CJW\ConfigProcessorBundle\src\ParametersToFileWriter;
use App\CJW\ConfigProcessorBundle\src\Utility\Utility;
use Exception;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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

        return $this->render("@CJWConfigProcessor/pagelayout.html.twig");
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

    public function getCurrentSAParameters (Request $request) {
        $saParameters = ConfigProcessCoordinator::getSiteAccessParameters();
        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $currentSiteAccess = $request->attributes->get("siteaccess")->name;
        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess.html.twig",
            [
                "siteAccess" => $currentSiteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "siteAccessParameters" => $saParameters
            ]
        );
    }

    public function getSpecificSAParameters (string $siteAccess) {
        try {
            $specSAParameters = ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
        } catch (InvalidArgumentException | Exception $error) {
            $specSAParameters = [];
        }

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess.html.twig",
            [
                "siteAccess" => $siteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "siteAccessParameters" => $specSAParameters
            ]
        );
    }

    public function compareSiteAccesses (string $firstSiteAccess, string $secondSiteAccess) {

        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);

        $firstSiteAccessParameters = $resultParameters[0];
        $secondSiteAccessParameters = $resultParameters[1];

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess_compare.html.twig",
            [
                "firstSiteAccess" => $firstSiteAccess,
                "secondSiteAccess" => $secondSiteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "firstSiteAccessParameters" => $firstSiteAccessParameters,
                "secondSiteAccessParameters" => $secondSiteAccessParameters,
                "limiter" => "Unlimited View",
            ]
        );
    }

    public function compareSiteAccessesCommonsOnly (string $firstSiteAccess, string $secondSiteAccess) {

        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $resultParameters = Utility::removeUncommonParameters($resultParameters[0],$resultParameters[1]);

        $firstSiteAccessParameters = $resultParameters[0];
        $secondSiteAccessParameters = $resultParameters[1];

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess_compare.html.twig",
            [
                "firstSiteAccess" => $firstSiteAccess,
                "secondSiteAccess" => $secondSiteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "firstSiteAccessParameters" => $firstSiteAccessParameters,
                "secondSiteAccessParameters" => $secondSiteAccessParameters,
                "limiter" => "Common Parameter View",

            ]
        );
    }

    public function compareSiteAccessesUncommonsOnly (string $firstSiteAccess, string $secondSiteAccess) {

        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $resultParameters = Utility::removeCommonParameters($resultParameters[0],$resultParameters[1]);

        $firstSiteAccessParameters = $resultParameters[0];
        $secondSiteAccessParameters = $resultParameters[1];

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess_compare.html.twig",
            [
                "firstSiteAccess" => $firstSiteAccess,
                "secondSiteAccess" => $secondSiteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "firstSiteAccessParameters" => $firstSiteAccessParameters,
                "secondSiteAccessParameters" => $secondSiteAccessParameters,
                "limiter" => "Uncommon Parameter View"
            ]
        );

    }

    public function downloadParameterListAsTextFile(string $siteAccessOrAllParameters) {
        if ($siteAccessOrAllParameters === "all_parameters") {
            $resultingFile = ParametersToFileWriter::writeParametersToFile(
                ConfigProcessCoordinator::getProcessedParameters()
            );
        } else {
            $resultingFile = ParametersToFileWriter::writeParametersToFile(
                ConfigProcessCoordinator::getParametersForSiteAccess(
                    $siteAccessOrAllParameters
                ),
                $siteAccessOrAllParameters
            );
        }

        $response = new BinaryFileResponse($resultingFile);
        $response->headers->set("Content-Type", "text/yaml");

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($resultingFile)
        );

        return $response;
    }

    private function retrieveParamsForSiteAccesses (string $firstSiteAccess, string $secondSiteAccess) {
        $firstSiteAccessParameters = [];
        $secondSiteAccessParameters = [];

        try {
            $firstSiteAccessParameters = ConfigProcessCoordinator::getParametersForSiteAccess($firstSiteAccess);
            $secondSiteAccessParameters = ConfigProcessCoordinator::getParametersForSiteAccess($secondSiteAccess);
        } catch (InvalidArgumentException | Exception $error) {
            $firstSiteAccessParameters = (count($firstSiteAccessParameters) > 0)? $firstSiteAccessParameters : [];
            $secondSiteAccessParameters = (count($secondSiteAccessParameters) > 0)? $secondSiteAccessParameters : [];
        }

        return [$firstSiteAccessParameters,$secondSiteAccessParameters];
    }
}
