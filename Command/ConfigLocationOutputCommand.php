<?php


namespace CJW\CJWConfigProcessor\Command;


use CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle\LocationRetrievalCoordinator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ConfigLocationOutputCommand is a console command to display all recorded locations from which parameters of the
 * configuration have been set.
 *
 * @package CJW\CJWConfigProcessor\Command
 */
class ConfigLocationOutputCommand extends Command
{
    protected static $defaultName = "cjw:output-locations";

    /**
     * @override
     *
     * Configures the command and the parameters / options that can be set for it.
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription("Displays the determined config paths (parameter origins) for the Symfony application.")
            ->setHelp(<<<'EOD'
This console command allows a user to access the a list of all paths (leading to files where config parameters have
either been set or overwritten) for the configuration of the Symfony application the bundle was able to determine.
The following options can be set for the command, but these are purely optional:

--paramname or -p:  If a specific parameter name is given (i.e. "ezsettings.default.api_keys"), only paths for that
                    specific parameter are displayed (excluding every other parametername). The name does have to be
                    exact and if the option is omitted, then every found path is displayed.

To better read and format the output it is advised to pipe the output of this command to "LESS", if you are using an
ubuntu operating system.

Example: "php bin/console cjw:output-locations | less"

Then you can scroll more easily through the output and the output is present in an other context that can be quitted
with "q", so that the console is not spammed with the command output. Then you can also search something by typing "/"
and then the search word + enter key.
EOD
            )
            ->addOption(
                "paramname",
                "p",
                InputOption::VALUE_OPTIONAL,
                "Giving a parametername will filter the list for that specific parameter and only display paths belonging to that parameter",
                false
            );

    }

    /**
     * @override
     * Controls the command execution.
     *
     * @param InputInterface $input The input the user can provide to the command.
     * @param OutputInterface $output Controls the output that is supposed to be written out to the user.
     *
     * @return int Returns the execution status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ioStyler = new SymfonyStyle($input, $output);
        $filterParameters = $input->getOption("paramname");

        if ($filterParameters) {
            $parametersAndPaths = LocationRetrievalCoordinator::getParameterLocations($filterParameters);
        } else {
            $parametersAndPaths = LocationRetrievalCoordinator::getParametersAndLocations();
        }

        $ioStyler->note([
            "This command will now run with the following options:",
            "Parameter Filter: " . $filterParameters ?? "none",
        ]);

        if ($parametersAndPaths && $this->outputArray($output, $parametersAndPaths)) {
            $ioStyler->newLine();
            $ioStyler->success("Command ran successfully.");
        } else {
            $ioStyler->error("No parameters could be found for this option.");
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
            $key = str_pad($key, $indents + strlen($key), " ", STR_PAD_LEFT);

            $output->write($key . ": ");
            if (is_array($parameter)) {
                if (count($parameter) > 0) {
                    $output->write(str_repeat(" ", $indents) . "\n");
                    $this->outputArray($output, $parameter, $indents + 4);
                    $output->write(str_repeat(" ", $indents) . "\n");
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
