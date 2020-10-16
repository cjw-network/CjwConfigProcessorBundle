<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use App\CJW\ConfigProcessorBundle\src\ConfigProcessor;
use App\CJW\ConfigProcessorBundle\ParameterAccessBag;
use App\CJW\ConfigProcessorBundle\src\SiteAccessParamProcessor;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Twig\TwigFunction;

/**
 * Class TwigConfigDisplayService.
 * This class is responsible for delivering the information about the internal set parameters of the symfony application
 * to twig templates in the form of both functions and global variables. It therefore also possesses capabilities of processing
 * the internal options.
 *
 * @package App\CJW\ConfigProcessorBundle\Services
 */
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
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
        ConfigProcessCoordinator::startProcess();
        $this->processedParameters = ConfigProcessCoordinator::getProcessedParameters();
        $this->siteAccessParameters = ConfigProcessCoordinator::getSiteAccessParameters();
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                "cjw_process_parameters",
                array($this, "getProcessedParameters"),
                array("is_safe" => array("html")),
            ),
            new TwigFunction(
              "cjw_process_for_current_siteaccess",
              array($this, "getParametersForCurrentSiteAccess"),
              array("is_safe" => array("html")),
            ),
            new TwigFunction(
                "cjw_process_for_siteaccess",
                array($this, "getParametersForSpecificSiteAccess"),
                array("is_safe" => array("html")),
            ),
            new TwigFunction(
                "is_numeric",
                array($this, "isNumeric"),
                array("is_safe" => array("html")),
            ),
            new TwigFunction(
                "is_string",
                array($this, "isString"),
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

    public function getProcessedParameters(): array {
        try {
            return ConfigProcessCoordinator::getProcessedParameters();
        } catch (Exception $e) {
            echo("Something went wrong while trying to retrieve the processed parameters.");
            return [];
        }
    }

    public function getParametersForCurrentSiteAccess(): array {
        try {
            return ConfigProcessCoordinator::getSiteAccessParameters();
        } catch (Exception $error) {
            return [];
        }
    }

    public function getParametersForSpecificSiteAccess(string $siteAccess): array {
        try {
            return ConfigProcessCoordinator::getParametersForSpecificSiteAccess($siteAccess);
        } catch (Exception $error) {
            return [];
        }
    }

    //Helper functions in twig templates

    public function isNumeric($value): bool {
        return is_numeric($value);
    }

    public function isString($value): bool {
        return is_string($value);
    }
}
