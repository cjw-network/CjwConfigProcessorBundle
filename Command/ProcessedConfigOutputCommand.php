<?php

namespace CJW\CJWConfigProcessor\Command;

use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\ConfigProcessCoordinator;
use CJW\CJWConfigProcessor\src\ConfigProcessorBundle\CustomParamProcessor;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
    protected function configure()
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

   To better read and format the output it is advised to pipe the output of this command to "LESS", if you are using an
   ubuntu operating system.

   Example: "php bin/console cjw:output-config | less"

   Then you can scroll more easily through the output and the output is present in an other context that can be quitted
   with "q", so that the console is not spammed with the command output. Then you can also search something by typing "/"
   and then the search word + enter key.
  EOD
            )
            // TODO: Turn paramname into an array, so that multiple branches can be filtered for.
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
     * @override
     * Controls the commands execution.
     *
     * @param InputInterface $input The input the user can provide to the command.
     * @param OutputInterface $output Controls the output that is supposed to be written out to the user.
     *
     * @return int Returns the execution status code.
     *
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ioStyler = new SymfonyStyle($input, $output);
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
                $siteAccess = ConfigProcessCoordinator::getSiteAccessListForController($siteAccess);
                $this->customParameterProcessor->setSiteAccessList($siteAccess);
                $processedParameters = $this->customParameterProcessor->scanAndEditForSiteAccessDependency($processedParameters);
            }
        }

        $ioStyler->note([
            "The command will run with the following options:",
            "SiteAccess: ". $siteAccessContext,
            "Parameter filter: ". $filterParameters,
        ]);

        if ($processedParameters && $this->outputArray($output,$processedParameters)) {
            $ioStyler->success("Command ran successfully.");
        } else {
            $ioStyler->error("No parameters could be found for these options.");
        }

        return 0;
    }

    /**
     * Builds the string output for the command. Handles hierarchical, multi dimensional arrays.
     *
     * @param OutputInterface $output The interface used to output the contents of the array.
     * @param array $parameters The array to be output.
     * @param int $indents The number of indents to be added in front of the output lines.
     *
     * @return bool Returns boolean stating whether parameters could successfully be found and output or not.
     */
    private function outputArray(OutputInterface $output, array $parameters, $indents = 0)
    {
        if (count($parameters) === 0) {
            return false;
        }

        foreach ($parameters as $key => $parameter) {
            $key = str_pad($key,$indents+strlen($key), " ", STR_PAD_LEFT);

            $output->write($key.": ");
            if (is_array($parameter)) {
                if ( count($parameter) > 0) {
                    $output->write(str_repeat(" ", $indents)."\n");
                    $this->outputArray($output,$parameter, $indents+4);
                    $output->write(str_repeat(" ", $indents)."\n");
                } else {
                    $output->writeln(" ");
                }
            } else {
                $output->writeln($parameter);
            }
        }

        return true;
    }
}
