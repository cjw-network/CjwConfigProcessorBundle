<?php


namespace App\CJWConfigProcessorBundle\Controller;


use App\CJWConfigProcessorBundle\src\ConfigProcessorBundle\ConfigProcessCoordinator;
use App\CJWConfigProcessorBundle\src\ConfigProcessorBundle\FavouritesParamCoordinator;
use App\CJWConfigProcessorBundle\src\ConfigProcessorBundle\ParametersToFileWriter;
use App\CJWConfigProcessorBundle\src\Utility\Utility;
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
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        if (
            $this->container->getParameter("cjw.favourite_parameters.allow") === true ||
            $this->container->getParameter("cjw.custom_site_access_parameters.active") === true
        ) {
            FavouritesParamCoordinator::initialize($this->container);
        }

        $this->showFavouritesOutsideDedicatedView =
            $this->container->getParameter("cjw.favourite_parameters.display_everywhere");
    }

    public function getStartPage () {
        try {
            ConfigProcessCoordinator::startProcess();
        } catch (Exception $e) {
            throw new HttpException(500);
        }

        return $this->render("@CJWConfigProcessor/pagelayout.html.twig");
    }

    public function getParameterList () {
        try {
            $parameters = ConfigProcessCoordinator::getProcessedParameters();
            $favourites = $this->showFavouritesOutsideDedicatedView ?
                FavouritesParamCoordinator::getFavourites($parameters) : [];
            $lastUpdated = ConfigProcessCoordinator::getTimeOfLastUpdate();

            return $this->render(
                "@CJWConfigProcessor/full/param_view.html.twig",
                [
                    "parameterList" => $parameters,
                    "favourites" => $favourites,
                    "lastUpdated" => $lastUpdated,
                ]
            );
        } catch (Exception $error) {
            throw new HttpException(500, "Something went wrong while trying to gather the required parameters.");
        }
    }

    public function getSpecificSAParameters (Request $request, string $siteAccess = null) {
        try {
            $specSAParameters = ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        } catch (InvalidArgumentException $error) {
            $specSAParameters = [];
        } catch (Exception $error) {
            throw new HttpException(500, "Couldn't collect the required parameters internally.");
        }

        if (!$siteAccess) {
            $siteAccess = $request->attributes->get("siteaccess")->name;
        }

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);
        $siteAccessesToScanFor = ConfigProcessCoordinator::getSiteAccessListForController($siteAccess);

        $favourites = $this->showFavouritesOutsideDedicatedView ?
            FavouritesParamCoordinator::getFavourites($processedParameters, $siteAccessesToScanFor) : [];
        $lastUpdated = ConfigProcessCoordinator::getTimeOfLastUpdate();

        return $this->render(
            "@CJWConfigProcessor/full/param_view_siteaccess.html.twig",
            [
                "siteAccess" => $siteAccess,
                "allSiteAccesses" => $siteAccesses,
                "allSiteAccessGroups" => $groups,
                "siteAccessParameters" => $specSAParameters,
                "favourites" => $favourites,
                "lastUpdated" => $lastUpdated,
            ]
        );
    }

    public function compareSiteAccesses (
        string $firstSiteAccess,
        string $secondSiteAccess,
        string $limiter = null
    ) {
        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $resultFavourites =
            $this->retrieveFavouritesForSiteAccesses($processedParameters,$firstSiteAccess,$secondSiteAccess);
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

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);
        $lastUpdated = ConfigProcessCoordinator::getTimeOfLastUpdate();

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
                "lastUpdated" => $lastUpdated,
            ]
        );
    }

    public function getFavourites (string $siteAccess = null) {
        try {
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        } catch (Exception $error) {
            throw new HttpException(500, "Couldn't collect the required parameters.");
        }

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);
        $siteAccessesToScanFor = $siteAccess?
            ConfigProcessCoordinator::getSiteAccessListForController($siteAccess) : [];

        $favourites = FavouritesParamCoordinator::getFavourites($processedParameters,$siteAccessesToScanFor);

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
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
            $request = json_decode($requestData);
            FavouritesParamCoordinator::setFavourite($request, $processedParameters);
        } catch (Exception $error) {
            throw new BadRequestException("The given data was not of a json format!");
        }

        return new Response(null, 200);
    }

    public function removeFavourites (Request $request): Response {
        $requestData = $request->getContent();

        try {
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
            $request = json_decode($requestData);
            FavouritesParamCoordinator::removeFavourite($request,$processedParameters);
        } catch (Exception $error) {
            throw new BadRequestException("The given data is of the wrong (non-json) format!");
        }

        return new Response(null, 200);
    }

    public function downloadParameterListAsTextFile(string $downloadDenominator): BinaryFileResponse {
        try {
            if ($downloadDenominator === "all_parameters") {
                $resultingFile = ParametersToFileWriter::writeParametersToFile(
                    ConfigProcessCoordinator::getProcessedParameters()
                );
            } else if ($downloadDenominator === "favourites") {
                $resultingFile = ParametersToFileWriter::writeParametersToFile(
                    FavouritesParamCoordinator::getFavourites(
                        ConfigProcessCoordinator::getProcessedParameters()
                    ),
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
        } catch (InvalidArgumentException | Exception $error) {
            throw new HttpException(
                500,
                "Something went wrong while trying to collect the requested parameters for download."
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

    private function retrieveParamsForSiteAccesses (
        string $firstSiteAccess,
        string $secondSiteAccess
    ) {
        $firstSiteAccessParameters = [];
        $secondSiteAccessParameters = [];

        try {
            $firstSiteAccessParameters =
                ConfigProcessCoordinator::getParametersForSiteAccess($firstSiteAccess);
            $secondSiteAccessParameters =
                ConfigProcessCoordinator::getParametersForSiteAccess($secondSiteAccess);
        } catch (InvalidArgumentException | Exception $error) {
            $firstSiteAccessParameters = (count($firstSiteAccessParameters) > 0) ?
                $firstSiteAccessParameters : [];
            $secondSiteAccessParameters = (count($secondSiteAccessParameters) > 0) ?
                $secondSiteAccessParameters : [];
        }

        return [$firstSiteAccessParameters,$secondSiteAccessParameters];
    }

    private function retrieveFavouritesForSiteAccesses(
        array $processedParameters,
        string $firstSiteAccess,
        string $secondSiteAccess
    ) {
        $firstFavourites = [];
        $secondFavourites = [];

        $firstSiteAccesses =
            ConfigProcessCoordinator::getSiteAccessListForController($firstSiteAccess);
        $secondSiteAccesses =
            ConfigProcessCoordinator::getSiteAccessListForController($secondSiteAccess);

        if ($this->showFavouritesOutsideDedicatedView) {
            $firstFavourites =
                FavouritesParamCoordinator::getFavourites($processedParameters, $firstSiteAccesses);
            $secondFavourites =
                FavouritesParamCoordinator::getFavourites($processedParameters,$secondSiteAccesses);
        }

        return[$firstFavourites,$secondFavourites];
    }
}
