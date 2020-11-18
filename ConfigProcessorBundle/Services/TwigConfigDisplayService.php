<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessCoordinator;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Twig\TwigFilter;
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
            new TwigFunction(
                "is_content_iterable",
                array($this, "isContentIterable"),
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

    public function getFilters()
    {
        return array(
            new TwigFilter("boolean", array($this, "booleanFilter")),
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
            return ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
        } catch (Exception $error) {
            return [];
        }
    }

    //Helper functions in twig templates

    public function isNumeric(...$value): bool {
        if (count($value) === 1 && isset($value[0]) && is_array($value[0])) {
            $value = $value[0];
        }

        foreach ($value as $singleValue) {
            if (!is_numeric($singleValue)) {
                return false;
            }
        }

        return true;
    }

    public function isString($value): bool {
        return is_string($value);
    }

    public function isContentIterable(...$value) {
        if (count($value) === 1 && isset($value[0]) && is_array($value[0])) {
            $value = $value[0];
        }

        foreach($value as $singleValue) {
            if (is_array($singleValue)) {
                return true;
            }
        }

        return false;
    }

    public function booleanFilter ($value) {
        if (is_bool($value)) {
            return $value? "true" : "false";
        } else {
            return $value;
        }
    }
}
