<?php

namespace CJW\CJWConfigProcessor\Command;

use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\ConfigProcessCoordinator;
use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\CustomParamProcessor;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ProcessedConfigOutputCommand serves as a Symfony console command to display the processed configuration both
 * on its own but also within a site access context and or filtered to specific branches.
 *
 * @package CJW\CJWConfigProcessor\Command
 */
class ProcessedConfigOutputCommand extends Command
{
    protected static $defaultName = "cjw:output-config";

    /**
     * @var CustomParamProcessor Required to filter the configuration for specific, given parameters.
     */
    private $customParameterProcessor;

    public function __construct(ContainerInterface $symContainer, ConfigResolverInterface $ezConfigResolver, RequestStack $symRequestStack)
    {
        ConfigProcessCoordinator::initializeCoordinator($symContainer,$ezConfigResolver,$symRequestStack);
        $this->customParameterProcessor = new CustomParamProcessor($symContainer);

        parent::__construct();
    }

    /**
     * @override
     *
     * Used to configure the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription("Displays the processed config of the symfony application.")
            ->setHelp(<<<'EOD'
  This console command allows outputting the configuration made by the bundle to the console with a few options
  that can be used to customize the output. The following options can be set, but they are purely optional:

  --paramname or -p:    If a specific parameter name or segment is given (i.e. "ezpublish" or "ezpublish.default.siteaccess"),
                        only the corresponding section of the processed configuration will be displayed. To input a specific
                        parameter name, simply add it after the option with a "=".
                        (i.e. "php bin/console cjw:output-config --paramname=param_name").

  --siteaccess-context or -s:
                        To specify a specific site access context under which to view the parameter, simply add the context after
                        the option itself (i.e. "-s admin")

   If the site access and the parameter name option are given at the same time, the filtered and narrowed list will be
   viewed under site access context (not the complete list).
  EOD
            )
            // TODO: Turn paramname into an array, so that multiple branches can be fltered for.
            ->addOption(
                "paramname",
                "p",
                InputOption::VALUE_OPTIONAL,
                "Narrow the list down to a specific branch or parameter. Simply state the parameter key or segment to filter for.",
                false,
            )
            ->addOption(
                "siteaccess-context",
                "s",
                InputOption::VALUE_OPTIONAL,
                "Define the site access context under which the config should be displayed.",
                false,
            );
    }

    /**
     * Controls the commands execution.
     *
     * @param InputInterface $input The input the user can give to the command.
     * @param OutputInterface $output Controls the output that is supposed to be written out to the user.
     *
     * @return int Returns the execution status code.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $siteAccessContext = $input->getOption("siteaccess-context");
        $filterParameters = $input->getOption("paramname");

        $processedParameters = ConfigProcessCoordinator::getProcessedParameters();

        if ($filterParameters) {
            $processedParameters = $this->customParameterProcessor->getCustomParameters([$filterParameters], $processedParameters);
        }

        if ($siteAccessContext) {
            $siteAccess = $siteAccessContext;

            if (!$filterParameters) {
                $processedParameters = ConfigProcessCoordinator::getParametersForSiteAccess($siteAccess);
            } else  {
                $this->customParameterProcessor->setSiteAccessList([$siteAccess]);
                $processedParameters = $this->customParameterProcessor->scanAndEditForSiteAccessDependency($processedParameters);
            }
        }

        $this->outputArray($output,$processedParameters);

        return self::SUCCESS;

    }

    /**
     * Builds the string output for the command. Handles hierarchical, multi dimensional arrays.
     *
     * @param OutputInterface $output The interface used to output the contents of the array.
     * @param array $parameters The array to be output.
     * @param int $indents The number of indents to be added in front of the output lines.
     */
    private function outputArray(OutputInterface $output, array $parameters, int $indents = 0): void
    {
        if (count($parameters) === 0) {
            $output->writeln("No parameters could be found for these options.");
            return;
        }

        foreach ($parameters as $key => $parameter) {
            $key = str_pad($key,$indents+strlen($key), " ", STR_PAD_LEFT);

            $output->write($key.": ");
            if (is_array($parameter)) {
                if ( count($parameter) > 0) {
//                    $output->write("\n".str_repeat(" ", $indents)."["."\n");
                    $output->write(str_repeat(" ", $indents)."\n");
                    $this->outputArray($output,$parameter, $indents+4);
//                    $output->write(str_repeat(" ", $indents)."]"."\n");
                    $output->write(str_repeat(" ", $indents)."\n");
                } else {
                    $output->writeln(" ");
                }
            } else {
                $output->writeln($parameter);
            }
        }
    }
}
