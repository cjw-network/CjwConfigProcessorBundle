<?php


namespace CJW\CJWConfigProcessor\Controller;


use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\ConfigProcessCoordinator;
use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\FavouritesParamCoordinator;
use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\ParametersToFileWriter;
use CJW\CJWConfigProcessor\src\Utility\Utility;
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

/**
 * Class ConfigProcessController is responsible for delivering the functionality required to bring the processed
 * configuration into the bundle frontend.
 *
 * @package CJW\CJWConfigProcessor\Controller
 */
class ConfigProcessController extends AbstractController
{
    /**
     * @var bool Not of much use at the moment.
     */
    private $showFavouritesOutsideDedicatedView;

    /**
     * ConfigProcessController constructor.
     *
     * @param ContainerInterface $symContainer
     * @param ConfigResolverInterface $ezConfigResolver
     * @param RequestStack $symRequestStack
     */
    public function __construct (ContainerInterface $symContainer, ConfigResolverInterface $ezConfigResolver, RequestStack $symRequestStack)
    {
        $this->container = $symContainer;
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
        FavouritesParamCoordinator::initialize($this->container);

        $this->showFavouritesOutsideDedicatedView =
            $this->container->getParameter("cjw.favourite_parameters.display_everywhere");
    }

    /**
     * Currently unused function to only render the base layout template without any
     * processed configuration or additional functionality.
     *
     * @return Response|null Returns the baselayout template to be rendered or an exception if something went wrong.
     */
    public function getStartPage () {
        try {
            ConfigProcessCoordinator::startProcess();
        } catch (Exception $e) {
            throw new HttpException(500);
        }

        return $this->render("@CJWConfigProcessor/pagelayout.html.twig");
    }

    /**
     * Responsible for delivering the processed configuration for the frontend.
     * Takes the processed configuration and renders it into a template.
     *
     * @return Response|null Returns a rendered template.
     *
     * @throws InvalidArgumentException If something went wrong with the caching mechanism, this exception is thrown.
     */
    public function getParameterList ()
    {
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

    /**
     * Responsible for bringing the single view of specific site access parameters into the frontend.
     * If not site access context is given to the function, then simply the current site access of the request is used.
     *
     * @param Request $request The request made to the Symfony server.
     * @param string $siteAccess The optional site access context, if non is given, then the current one of the request is used.
     *
     * @return Response|null Returns a rendered template with site access specific parameters.
     *
     * @throws InvalidArgumentException
     */
    public function getSpecificSAParameters (Request $request, $siteAccess = null)
    {
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

    /**
     * Responsible for providing the site access comparison view to the bundle frontend.
     *
     * @param string $firstSiteAccess The first site access context for the first list of parameters in the comparison.
     * @param string $secondSiteAccess The second site access context for the second list of parameters in the comparison.
     * @param string|null $limiter An optional filter for the comparison (can be for common parameters only or uncommon, no filter if nothing is sent).
     *
     * @return Response|null Returns the rendered comparison template.
     *
     * @throws Exception Throws an exception if somethin went wrong during the process.
     */
    public function compareSiteAccesses ($firstSiteAccess, $secondSiteAccess, $limiter = null)
    {
        // Determine and retrieve the site access configuration for the two site accesses.
        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        $resultParameters = $this->retrieveParamsForSiteAccesses($firstSiteAccess,$secondSiteAccess);
        $resultFavourites =
            $this->retrieveFavouritesForSiteAccesses($processedParameters,$firstSiteAccess,$secondSiteAccess);
        $limiterString = "Default Comparison";

        // Filter the results if one is set.
        if ($limiter === "commons") {
            $resultParameters = Utility::removeUncommonParameters($resultParameters[0],$resultParameters[1]);
            $resultFavourites = Utility::removeUncommonParameters($resultFavourites[0],$resultFavourites[1]);
            $limiterString = "Commons Comparison";
        } else if ($limiter === "uncommons") {
            $resultParameters = Utility::removeCommonParameters($resultParameters[0],$resultParameters[1]);
            $resultFavourites = Utility::removeCommonParameters($resultFavourites[0],$resultFavourites[1]);
            $limiterString = "Differences Comparison";
        }

        // Provide the different values to the templates.
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

    /**
     * Retrieve the favourite parameters for the dedicated favourites view.
     *
     * @param string $siteAccess The (optional) site access context in which to view the favourite parameters.
     *
     * @return Response Returns the rendered favourite view template.
     *
     * @throws InvalidArgumentException Throws this error if something went wrong with the caching mechanism behind the favourites.
     */
    public function getFavourites ($siteAccess = null): Response
    {
        try {
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        } catch (Exception $error) {
            throw new HttpException(500, "Couldn't collect the required parameters.");
        }

        $siteAccesses = Utility::determinePureSiteAccesses($processedParameters);
        $groups = Utility::determinePureSiteAccessGroups($processedParameters);
        $siteAccessesToScanFor = $siteAccess?
            ConfigProcessCoordinator::getSiteAccessListForController($siteAccess) : [];

        $favourites =
            FavouritesParamCoordinator::getFavourites($processedParameters,$siteAccessesToScanFor);

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

    /**
     * Returns only a list of keys of the parameters that have been marked as favourites. It returns these
     * in a json format for further processing in the frontend.
     *
     * <br>Example format for the keys: ["favourite.key.one", "favourite.key.second", "third.favourite.key"]
     *
     * @return Response Returns the list of favourite parameter keys as a flat json array.
     *
     * @throws InvalidArgumentException Throws this error if something went wrong with the caching mechanism behind the favourite parameters.
     */
    public function getFavouriteKeyList (): Response
    {
        try {
            $processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        } catch (Exception $error) {
            throw new HttpException(500, "Couldn't collect the required parameters.");
        }

        $favourites =
            FavouritesParamCoordinator::getFavouriteKeyList($processedParameters);

        return $this->json($favourites);
    }

    /**
     * Allows saving a or multiple parameter keys as favourites in the application based on a sent
     * json array of keys.
     *
     * <br>Example format for the keys: ["favourite.key.one", "favourite.key.second", "third.favourite.key"]
     *
     * @param Request $request The request which includes the favourite keys a json array.
     *
     * @return Response Returns 200 if no error was encountered with the sent data.
     *
     * @throws InvalidArgumentException Throws this error if something went wrong with the caching mechanism behind the favourite parameters.
     */
    public function saveFavourites(Request $request): Response
    {
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

    /**
     * Allows removing a or multiple parameter keys from the favourites in the application based on a
     * sent json array of keys.
     *
     * <br>Example format for the keys: ["favourite.key.one", "favourite.key.second", "third.favourite.key"]
     *
     * @param Request $request The request which includes the keys to be removed as a json array.
     *
     * @return Response Returns 200 if no error was encountered with the sent data.
     *
     * @throws InvalidArgumentException Throws this error if something went wrong with the caching mechanism behind the favourite parameters.
     */
    public function removeFavourites (Request $request): Response
    {
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

    /**
     * Allows to determine a specific list of parameters to be brought into a file representation and
     * made available for download by the user.
     *
     * @param string $downloadDescriptor A string which determines which parameters are supposed to be written to
     *                                    a file ("all_parameters" = all parameters, "favourites" = only favourites,
     *                                    "[site access]" all parameters for that site access).
     *
     * @return BinaryFileResponse Returns the file which has been created through the selected parameters.
     */
    public function downloadParameterListAsTextFile($downloadDescriptor): BinaryFileResponse
    {
        try {
            if ($downloadDescriptor === "all_parameters") {
                $resultingFile = ParametersToFileWriter::writeParametersToFile(
                    ConfigProcessCoordinator::getProcessedParameters(),
                );
            } else if ($downloadDescriptor === "favourites") {
                $resultingFile = ParametersToFileWriter::writeParametersToFile(
                    FavouritesParamCoordinator::getFavourites(
                        ConfigProcessCoordinator::getProcessedParameters()
                    ),
                    $downloadDescriptor
                );
            } else {
                $resultingFile = ParametersToFileWriter::writeParametersToFile(
                    ConfigProcessCoordinator::getParametersForSiteAccess(
                        $downloadDescriptor
                    ),
                    $downloadDescriptor
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

    public function getEnvironmentalVariables ()
    {
        try {
            $lastUpdated = ConfigProcessCoordinator::getTimeOfLastUpdate();

            $envVar = [];

            if ($this->container->getParameter("cjw.env_variables.allow") === true) {
                $envVar = $_ENV;
            }

            return $this->render(
                "@CJWConfigProcessor/full/param_view_environment.html.twig",
                [
                    "parameterList" => $envVar,
                    "lastUpdated" => $lastUpdated,
                ]
            );
        } catch (Exception $error) {
            throw new HttpException(500, "Something went wrong while trying to gather the required parameters.");
        }
    }

    /**
     * Helper function to determine the specific parameters for both given site access contexts at the same time.
     * It returns the found parameters in an array in which the first entry marks all the parameters for the first
     * site access and the second every parameter for the second site access.
     *
     * @param string $firstSiteAccess The first site access for which to retrieve parameters.
     * @param string $secondSiteAccess The second site access for which to retrieve parameters.
     *
     * @return array A two dimensional array of arrays in which the first entry includes the parameters for the first site access
     *               and the second entry contains the parameters for the second site access.
     */
    private function retrieveParamsForSiteAccesses ($firstSiteAccess, $secondSiteAccess)
    {
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

    /**
     * Helper function to determine the favourite parameters for both given site access contexts at the same time.
     * It returns the found parameters in an array in which the first entry marks all the parameters for the first
     * site access and the second every parameter for the second site access.
     *
     * @param array $processedParameters The entire processed Symfony configuration to determine the favourite parameters if non had been set yet.
     * @param string $firstSiteAccess The first site access for which to retrieve favourites.
     * @param string $secondSiteAccess The second site access for which to retrieve favourites.
     *
     * @return array A two dimensional array of arrays in which the first entry includes the parameters for the first site access
     *               and the second entry contains the parameters for the second site access.
     *
     * @throws InvalidArgumentException Throws this error if something went wrong with the caching mechanism behind the favourites.
     */
    private function retrieveFavouritesForSiteAccesses(array $processedParameters, $firstSiteAccess, $secondSiteAccess)
    {
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
