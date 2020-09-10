<?php


namespace App\CJW\ConfigProcessorBundle\Services;


use App\CJW\ConfigProcessorBundle\src\ConfigProcessor;
use App\CJW\TestFrozenBag;
use Exception;
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
     * Contains all the processed parameters sorted after the corresponding site-accesses / scopes and also their
     * overall allegiance.
     *
     * @var array
     */
    private $processedParameters;

    /**
     * Contains an instance of a configProcessor which is responsible for
     * transforming and processing the config information given to it.
     *
     * @var ConfigProcessor
     */
    private $configProcessor;

    public function __construct(
        ContainerInterface $symContainer,
        ConfigResolverInterface $ezConfigResolver
    ) {
        $this->container = $symContainer;
        $this->configResolver = $ezConfigResolver;
        $this->configProcessor = new ConfigProcessor();
        try {
            $this->processedParameters = $this->parseContainerParameters();
        } catch (Exception $error) {
            print(`Something went wrong while trying to parse the parameters: ${$error}.`);
        }
//        $this->processedParameters = array();
    }

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

    public function getGlobals(): array
    {
        return array(
            "cjw_formatted_parameters" => $this->processedParameters,
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

    private function parseContainerParameters() {
        $parameters = $this->container->getParameterBag();

        $parameters = new TestFrozenBag($this->container);

        $parameters = $parameters->repurposeParameters();

        if ($parameters && is_array($parameters)) {
            $this->processedParameters = $this->configProcessor->processParameters($parameters);
        } else {
            throw new Exception("Something went wrong while trying to parse the parameters of the container.");
        }

        return $this->processedParameters;

//        return $parameters = array("Something", "Something else");
    }
}
