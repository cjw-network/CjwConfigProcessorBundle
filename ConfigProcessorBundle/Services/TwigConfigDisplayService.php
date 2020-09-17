<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessor;
use App\CJW\ConfigProcessorBundle\ParameterAccessBag;
use App\CJW\ConfigProcessorBundle\src\SiteAccessParamProcessor;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;
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
     * Contains the cache adapter of the class.
     *
     * @var PhpFilesAdapter
     */
    private $cache;

    /**
     * Contains an instance of a SiteAccessParamProcessor which is responsible for
     * filtering a given list of parameters for a given list of siteaccesses and resolve
     * the current values of said parameters.
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
        $this->cache = new PhpFilesAdapter();

        try {
            $this->request = $symRequestStack->getCurrentRequest();

            // If there is not stored parameter list in object form, then undo the rest of the cache
            if (!$this->cache->hasItem("processed_param_objects")) {
                $this->cache->delete("processed_params");
                $this->cache->delete("site_access_parameters");
            }

            $this->processedParameters = $this->cache->get("processed_params", function(ItemInterface $item) {
                $item->expiresAfter(300);

                return $this->parseContainerParameters();
            });

            if ($this->request) {

                $this->siteAccessParameters = $this->cache->get("site_access_parameters", function(ItemInterface $item) {
                    $item->expiresAfter(300);

                    return $this->getParametersForSiteAccess();
                });

            }
        } catch (Exception $error) {
            print(`Something went wrong while trying to parse the parameters: ${$error}.`);
        } catch (InvalidArgumentException $e) {
            print(`An error occured while trying to access caching for the parameters: ${$e}.`);
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
            new TwigFunction(
                "cjw_process_for_siteaccess",
                array($this, "getParametersForSpecificSiteAccess"),
                array("is_safe" => array("html")),
            ),
        );
    }

    /**
     * Provides all global variables for the twig template that stem from this bundle.
     *
     * @return array
     */
    public function getGlobals(): array
    {
        return array(
            "cjw_formatted_parameters" => $this->processedParameters,
            "cjw_siteaccess_parameters" => $this->siteAccessParameters,
        );

        // "cjw_test" => $this->getParametersForSpecificSiteAccess("admin"),
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
     * @param string $siteAccess
     * @return array
     * @throws InvalidArgumentException
     */
    public function getParametersForSpecificSiteAccess(string $siteAccess): array {
        $siteAccess = strtolower($siteAccess);

        $processedParamObj = $this->cache->get("processed_param_objects", function(ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->configProcessor->getProcessedParameters();
        });

        return $this->siteAccessParamProcessor->processSiteAccessBased(
            $this->getSiteAccesses($siteAccess),
            $processedParamObj,
            $siteAccess
        );
    }

    /***********************************************
     *
     * Private methods of the class which are called by the public functions.
     *
     ***********************************************/

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
     * @param string|null $siteAccess Optional parameter which dictates whether only the default SiteAccesses and the given one will be added or all available ones are added.
     * @return array Returns all found siteAccesses in an array.
     */
    private function getSiteAccesses(string $siteAccess = null): array {
        try {
            if (!$siteAccess) {
                $sa = $this->processedParameters["ezpublish"]["siteaccess"]["list"];
                array_push($sa, ...array_keys($this->processedParameters["ezpublish"]["siteaccess"]["groups"]));
            } else {
                $sa = array($siteAccess);
                array_push($sa,...$this->processedParameters["ezpublish"]["siteaccess"]["groups_by_siteaccess"][$siteAccess]);
            }

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
     * @throws InvalidArgumentException
     */
    private function getParametersForSiteAccess(): array {

        $processedParamObj = $this->cache->get("processed_param_objects", function(ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->configProcessor->getProcessedParameters();
        });

        return $this->siteAccessParamProcessor->processSiteAccessBased(
            $this->getSiteAccesses(),
            $processedParamObj,
        );
    }
}
