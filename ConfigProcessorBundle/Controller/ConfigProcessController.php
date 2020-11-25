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
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ConfigProcessController extends AbstractController
{
    private $showFavouritesOutsideDedicatedView;

    public function __construct (
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    )
    {
        $this->container = $symContainer;
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);

        if ($this->container->hasParameter("cjw.favourite_parameters.display_everywhere")) {
            $this->showFavouritesOutsideDedicatedView = $this->container->getParameter("cjw.favourite_parameters.display_everywhere");
        } else {
            $this->showFavouritesOutsideDedicatedView = false;
        }
    }

    public function getStartPage () {
        ConfigProcessCoordinator::startProcess();

        return $this->render("@CJWConfigProcessor/pagelayout.html.twig");
    }

    public function getParameterList () {
        $parameters = ConfigProcessCoordinator::getProcessedParameters();
        $favourites = $this->showFavouritesOutsideDedicatedView? ConfigProcessCoordinator::getFavourites() : [];

        return $this->render(
            "@CJWConfigProcessor/full/param_view.html.twig",
            [
                "parameterList" => $parameters,
                "favourites" => $favourites
            ]
        );
    }

    public function getSpecificSAParameters (Request $request, string $siteAccess = null) {
        try {
            $specSAParameters = ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
        } catch (InvalidArgumentException | Exception $error) {
            $specSAParameters = [];
        }

        if (!$siteAccess) {
            $siteAccess = $request->attributes->get("siteaccess")->name;
        }

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        $favourites = $this->showFavouritesOutsideDedicatedView? ConfigProcessCoordinator::getFavourites($siteAccess) : [];

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess.html.twig",
            [
                "siteAccess" => $siteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "siteAccessParameters" => $specSAParameters,
                "favourites" => $favourites
            ]
        );
    }

    public function compareSiteAccesses (string $firstSiteAccess, string $secondSiteAccess, string $limiter = null) {

        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $resultFavourites = $this->retrieveFavouritesForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $limiterString = "Unlimited View";

        if ($limiter === "commons") {
            $resultParameters = Utility::removeUncommonParameters($resultParameters[0],$resultParameters[1]);
            $resultFavourites = Utility::removeUncommonParameters($resultFavourites[0],$resultFavourites[1]);
            $limiterString = "Common Parameter View";
        } else if ($limiter === "uncommons") {
            $resultParameters = Utility::removeCommonParameters($resultParameters[0],$resultParameters[1]);
            $resultFavourites = Utility::removeCommonParameters($resultFavourites[0],$resultFavourites[1]);
            $limiterString = "Uncommon Parameter View";
        }

        $firstSiteAccessParameters = $resultParameters[0];
        $secondSiteAccessParameters = $resultParameters[1];

        $firstSiteAccessFavourites = $resultFavourites[0];
        $secondSiteAccessFavourites = $resultFavourites[1];

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
                "firstSiteAccessFavourites" => $firstSiteAccessFavourites,
                "secondSiteAccessFavourites" => $secondSiteAccessFavourites,
                "limiter" => $limiterString,
            ]
        );
    }

    public function getFavourites (string $siteAccess = null) {
        $favourites = ConfigProcessCoordinator::getFavourites($siteAccess);

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);

        return $this->render(
            "@CJWConfigProcessor/full/param_view_favourites.html.twig",
            [
                "siteAccess" => $siteAccess,
                "parameterList" => $favourites,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
            ]
        );
    }

    public function saveFavourites(Request $request): Response {
        $requestData = $request->getContent();

        try {
            $request = json_decode($requestData);
            ConfigProcessCoordinator::setFavourites($request);
        } catch (Exception $error) {
            throw new BadRequestException("The given data was not of a json format!");
        }

        return new Response(null, 200);
    }

    public function downloadParameterListAsTextFile(string $downloadDenominator): BinaryFileResponse {
        if ($downloadDenominator === "all_parameters") {
            $resultingFile = ParametersToFileWriter::writeParametersToFile(
                ConfigProcessCoordinator::getProcessedParameters()
            );
        } else if ($downloadDenominator === "favourites") {
            $resultingFile = ParametersToFileWriter::writeParametersToFile(
                ConfigProcessCoordinator::getFavourites(),
                $downloadDenominator
            );
        } else {
            $resultingFile = ParametersToFileWriter::writeParametersToFile(
                ConfigProcessCoordinator::getParametersForSiteAccess(
                    $downloadDenominator
                ),
                $downloadDenominator
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

    private function retrieveFavouritesForSiteAccesses(string $firstSiteAccess, string $secondSiteAccess) {
        $firstFavourites = [];
        $secondFavourites = [];

        if ($this->showFavouritesOutsideDedicatedView) {
            $firstFavourites = ConfigProcessCoordinator::getFavourites($firstSiteAccess);
            $secondFavourites = ConfigProcessCoordinator::getFavourites($secondSiteAccess);
        }

        return[$firstFavourites,$secondFavourites];
    }
}
