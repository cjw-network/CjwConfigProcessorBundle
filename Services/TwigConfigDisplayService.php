<?php


namespace CJW\CJWConfigProcessor\Services;


use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\ConfigProcessCoordinator;
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
 * @package CJW\CJWConfigProcessor\ConfigProcessorBundle\Services
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
              "cjw_process_for_siteaccess",
              array($this, "getParametersForSiteAccess"),
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
    public function getGlobals()
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

    /**
     * @return array Returns the processed parameters (Symfony configuration) into a twig template.
     */
    public function getProcessedParameters()
    {
        try {
            return ConfigProcessCoordinator::getProcessedParameters();
        } catch (Exception $e) {
            echo("Something went wrong while trying to retrieve the processed parameters.");
            return [];
        }
    }

    /**
     * @param string $siteAccess An optional parameter which determines what site access context to use for the retrieval (will use the current one of the request, when none is set)
     *
     * @return array Returns the site access parameters for the site access the current request uses to a twig template.
     */
    public function getParametersForSiteAccess($siteAccess = null)
    {
        try {
            return ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
        } catch (Exception $error) {
            return [];
        }
    }

    //Helper functions in twig templates

    /**
     * Determines whether given values are all numeric or not.
     *
     * @param mixed ...$value The values being put into the function.
     *
     * @return bool Returns true if every value is numeric and false if there is at least one that isn't.
     */
    public function isNumeric(...$value)
    {
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

    /**
     * Determines whether a given value is of type string or not.
     *
     * @param $value Value to be checked.
     *
     * @return bool Returns true is the value is of type string or false if it is not.
     */
    public function isString($value)
    {
        return is_string($value);
    }

    /**
     * Determines whether the given values are of type array or not.
     *
     * @param mixed ...$value The values to be checked.
     *
     * @return bool Returns true if the values are all of type array  and false if at least one is not.
     */
    public function isContentIterable(...$value)
    {
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

    /**
     * Turns a given boolean value into a string representation of its value.
     *
     * @param mixed $value The value to be filtered.
     *
     * @return string Returns a string representation of the boolean value.
     */
    public function booleanFilter ($value)
    {
        if (is_bool($value)) {
            return $value? "true" : "false";
        } else {
            return $value;
        }
    }
}
