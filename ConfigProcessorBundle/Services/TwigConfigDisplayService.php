<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessor;
use App\CJW\ConfigProcessorBundle\ParameterAccessBag;
use App\CJW\ConfigProcessorBundle\src\SiteAccessParamProcessor;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Twig\TwigFunction;

class TwigConfigDisplayService extends AbstractExtension implements GlobalsInterface
{
    /**
     * Contains the symfony container which stores all the parameters of the configuration gathered through
     * the yml files in the app.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Holds the eZ-Platform specific ConfigResolver which is responsible for delivering site access dependent configuration
     * parameters.
     *
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * Holds the current request for further processing.
     *
     * @var Request
     */
    private $request;

    /**
     * Contains all the processed parameters categorized after their namespaces and other keys within their name
     * down to the actual parameter.
     *
     * @var array
     */
    private $processedParameters;

    /**
     * Contains all parameters that have been matched to the site access of the current request.
     * These mostly resort to parameters already present in the processedParameters, but only the ones
     * specific to the current site access.
     * @see $processedParameters
     *
     * @var array
     */
    private $siteAccessParameters;

    /**
     * Contains an instance of a ConfigProcessor which is responsible for
     * transforming and processing the config information given to it.
     *
     * @var ConfigProcessor
     */
    private $configProcessor;

    /**
     * Contains an instance of a SiteAccessParamProcessor which is responsible for
     * filtering a given list of parameters for a given list of siteaccesses and resolve
     * the resolve the current values of said parameters.
     *
     * @var SiteAccessParamProcessor
     */
    private $siteAccessParamProcessor;

    public function __construct(
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver,
        RequestStack $symRequestStack
    ) {
        $this->container = $symContainer;
        $this->configResolver = $ezConfigResolver;
        $this->configProcessor = new ConfigProcessor();
        $this->siteAccessParamProcessor = new SiteAccessParamProcessor($this->configResolver);

        try {
            $this->request = $symRequestStack->getCurrentRequest();
            $this->processedParameters = $this->parseContainerParameters();
            if ($this->request) {
                $this->siteAccessParameters = $this->getParametersForSiteAccess();
            }
        } catch (Exception $error) {
            print(`Something went wrong while trying to parse the parameters: ${$error}.`);
        }
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                "cjw_process_parameters",
                array($this, "parseContainerParameters"),
                array("is_safe" => array("html")),
            ),
        );
    }

    /**
     * Provides all global variables for the twig template.
     *
     * @return array
     */
    public function getGlobals(): array
    {
        return array(
            "cjw_formatted_parameters" => $this->processedParameters,
            "cjw_siteaccess_parameters" => $this->siteAccessParameters,
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extensions name
     */
    public function getName()
    {
        return 'cjw_config_processor.twig.display';
    }

    /**
     * Parses the internal symfony parameters and provides the formatted parameters and options
     * as an array to the class.
     *
     * @return array Returns a hierarchical associative array that features every parameter sorted after their keys.
     * @throws Exception Throws an error if something went wrong while trying to parse the parameters.
     */
    private function parseContainerParameters() {
        $parameters = new ParameterAccessBag($this->container);
        $parameters = $parameters->getParameters();

        if ($parameters && is_array($parameters)) {
            $this->processedParameters = $this->configProcessor->processParameters($parameters);
        } else {
            throw new Exception("Something went wrong while trying to parse the parameters of the container.");
        }

        return $this->processedParameters;
    }

    /**
     * Simply goes into the ezpublish parameter to get the list of current site accesses that exist in the
     * parameter.
     *
     * @return array Returns all found siteAccesses in an array.
     */
    private function getSiteAccesses(): array {
        try {
            $sa = $this->processedParameters["ezpublish"]["siteaccess"]["list"];
            array_push($sa, "default", "global");
        } catch (Exception $error) {
            // Fallback SAs if the others are not accessible via the array route
            $sa = array("default","global");
        }
        return $sa;
    }

    /**
     * Takes the processed parameters and searches for all parameters and their values that
     * belong to the current site access.
     *
     * @return array Returns a formatted array that can be displayed in twig templates.
     */
    private function getParametersForSiteAccess(): array {
        return $this->siteAccessParamProcessor->processSiteAccessBased(
            $this->getSiteAccesses(),
            $this->configProcessor->getProcessedParameters()
        );
    }
}
