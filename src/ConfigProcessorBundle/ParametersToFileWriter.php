<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use DateTime;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ParametersToFileWriter is used to create a file representation of the parameter lists given to it.
 *
 * @package CJW\CJWConfigProcessor\src\ConfigProcessorBundle
 */
class ParametersToFileWriter
{
    /**
     * @var bool Stating whether the Writer has been initialized already.
     */
    private static $initialized = false;

    /**
     * @var Filesystem Used to create the file itself and write the content to file.
     */
    private static $filesystem;

    /**
     * Function to intialize the writer and set up the most important class attributes to function properly.
     */
    public static function initializeFileWriter ()
    {
        if (!self::$initialized) {

            if (!self::$filesystem) {
                self::$filesystem = new Filesystem();
            }
        }
    }

    /**
     * Writes a given associative array of parameters to a file in a yaml format.
     *
     * @param array $parametersToWrite An associative, hierarchical array of parameters.
     * @param string $downloadDescriptor A string which determines whether or not the file should be limited to
     *                                   or viewed in a specific context (favourites, site access, etc.).
     *
     * @return string Returns the name of / the path to the file that has been created.
     */
    public static function writeParametersToFile (array $parametersToWrite, $downloadDescriptor = null)
    {
        if (!self::$initialized) {
            self::initializeFileWriter();
        }

        $temporaryFile = self::$filesystem->tempnam(sys_get_temp_dir(),"parameter_list_", ".yaml");

        // Assemble a new and more readable name for the temporary file that is offered to be downloaded.
        $tmpDir = pathinfo($temporaryFile,PATHINFO_DIRNAME);
        $currentDate = new DateTime();
        $currentDate = $currentDate->format("Y-m-d_H.i");
        $downloadDescriptor = $downloadDescriptor?? "all_parameters";
        $targetName = $tmpDir."/parameter_list_".$downloadDescriptor."_".$currentDate.".yaml";

        // Start the file writing process only when the file does not already exist.
        if (!file_exists($targetName)) {
            if ($temporaryFile) {
                $siteAccess = null;
                if (!($downloadDescriptor === "favourites" || $downloadDescriptor === "all_parameters")) {
                    $siteAccess = $downloadDescriptor;
                }

                self::appendDataPerKey($temporaryFile,$parametersToWrite, $siteAccess);
            }

            self::$filesystem->rename($temporaryFile,$targetName);
        }

        return $targetName;
    }

    /**
     * For every top level key of the given array, the data is collected and written out to the file.
     * It will employ the top level key to write out the entirety of all sub-arrays / -keys.
     *
     * @param string $pathToFileToWriteTo The path to the file that is supposed to be written to.
     * @param array $parametersToWrite An associative array of parameters to be written out.
     * @param string|null $siteAccess The site access context in which to print out the parameter keys.
     */
    private static function appendDataPerKey ($pathToFileToWriteTo, array $parametersToWrite, $siteAccess = null)
    {
        // On the first line, make sure the "parameters"-key is written once.
        self::$filesystem->appendToFile($pathToFileToWriteTo,"parameters:\n");

        foreach (array_keys($parametersToWrite) as $key) {
            // Write out the top level key.
            self::$filesystem->appendToFile($pathToFileToWriteTo,"\n");
            $keyDisplay = $key;

            if ($siteAccess) {
                $keyDisplay .= ".".$siteAccess;
            }

            // Write out the remaining keys under that top level.
            self::writeSubTree($pathToFileToWriteTo, $parametersToWrite[$key],$keyDisplay);
        }
    }

    /**
     * Handles the output to file for the entirety of a multi dimensional associative array structure.
     * It determines the type of output based on whether the value of a parameter has started and
     * whether those values include "yaml objects" or simply "yaml lists".
     *
     * @param string $pathToFileToWriteTo The path to the file to which to write the output.
     * @param array $subTreeToWrite The array to be written into the file in a yaml format.
     * @param string $previousKey The key of the array which came the level above the current.
     * @param bool $valueReached Whether or not the value of the parameter has been reached.
     * @param int $numberOfIndents The number of indents to add to the line before writing it out.
     */
    private static function writeSubTree ($pathToFileToWriteTo, array $subTreeToWrite, $previousKey, $valueReached = false, $numberOfIndents = 0)
    {
        foreach ($subTreeToWrite as $parameterKey => $parameterFollowUp) {
            $parameterFollowUpIsArray = is_array($parameterFollowUp);

            if (!$parameterFollowUpIsArray) {
                // Is the value a boolean, then create a string representation in order to allow it to be written out to yaml without issue.
                if (is_bool($parameterFollowUp)) {
                    $parameterFollowUp = $parameterFollowUp? "true" : "false";
                } else {
                    // If the special character '"' is included (which cannot be used as is in yaml) escape the character.
                    if ($parameterFollowUp && str_contains($parameterFollowUp,"\"")) {
                        $parameterFollowUp = str_replace("\"","\\\"",$parameterFollowUp);
                    }

                    // To ensure that most characters and longer lines are properly escaped and wrapped from the start, enclose the string in quotes.
                    $parameterFollowUp = '"'.$parameterFollowUp.'"';
                }
            }

            // Ensure that no special characters are used as parameter keys and if they are, properly escape them.
            if (preg_match('/^[\'"^£$%&*()}{@#~?><,|=_+¬-]/', $parameterKey)) {
                // The quote needs to be additionally escaped to be usable as a key.
                if (str_contains($parameterKey,"\"")) {
                    $parameterKey = str_replace("\"","\\\"",$parameterKey);
                }

                $parameterKey = '"'.$parameterKey.'"';
            }


            if (!$valueReached) {
                if ($parameterFollowUpIsArray) {
                    self::writeMultiLineKeys (
                        $parameterKey,
                        $previousKey,
                        $parameterFollowUp,
                        $pathToFileToWriteTo,
                    );
                } else if ($parameterFollowUp) {
                    self::writeSingleLineKeys(
                        $parameterKey,
                        $previousKey,
                        $parameterFollowUp,
                        $pathToFileToWriteTo,
                    );
                }
            } else {
                if (is_numeric($parameterKey)) {
                    $parameterKey = "";
                }

                if ($parameterFollowUpIsArray) {
                    self::writeMultiLineValues(
                        $parameterFollowUp,
                        $pathToFileToWriteTo,
                        $numberOfIndents,
                        $parameterKey,
                    );
                } else if ($parameterFollowUp) {
                    self::writeInlineValues(
                        $parameterFollowUp,
                        $pathToFileToWriteTo,
                        $numberOfIndents,
                        $parameterKey,
                    );
                }
            }
        }
    }

    /**
     * Writes a key with or without a value attached to it into a single line in a yaml format.
     *
     * @param string $parameterKey The key that is supposed to be added to the file.
     * @param string $previousKey The key that came before it in the key hierarchy.
     * @param string $paramValue The value attached to the key to be written out to the file.
     * @param string $pathToWriteTo The path to the file to write to.
     */
    private static function writeSingleLineKeys ($parameterKey, $previousKey, $paramValue, $pathToWriteTo)
    {
        $fileInput = $previousKey . ": " . $paramValue . "\n";

        if (!$parameterKey === "parameter_value") {
            $parameterKey = $previousKey . "." . $parameterKey;
            $fileInput = $parameterKey. ":\n";
        }

        if ($paramValue) {
            self::$filesystem->appendToFile(
                $pathToWriteTo,
                self::buildOutputString(
                    $fileInput,
                    4
                )
            );
        }
    }

    /**
     * Writes out a key structure in its entirety to a file. That key structure then does not contain
     * a value as it rather is responsible for handling hierarchical key structures for the yaml.
     *
     * @param string $parameterKey The key that is supposed to be added to the file.
     * @param string $previousKey The key that came before it in the key hierarchy.
     * @param array $output The rest of the subtree structure beneath the parameter key.
     * @param string $pathToWriteTo The path to the file to write to.
     */
    private static function writeMultiLineKeys ($parameterKey, $previousKey, array $output, $pathToWriteTo)
    {
        $valueReached = false;
        $numberOfIndents = 0;

        if ($parameterKey === "parameter_value") {
            $valueReached = true;
            $numberOfIndents = 8;
            $parameterKey = $previousKey;

            self::$filesystem->appendToFile(
                $pathToWriteTo,
                self::buildOutputString(
                    $previousKey . ":\n",
                    4
                )
            );
        } else {
            $parameterKey = $previousKey . "." . $parameterKey;
        }

        self::writeSubTree(
            $pathToWriteTo,
            $output,
            $parameterKey,
            $valueReached,
            $numberOfIndents
        );
    }

    /**
     * Write a value that spans only a single line out to the file.
     *
     * @param string $parameterFollowUp The parameter value to write out to the file.
     * @param string $pathToFile The path leading to the file to write out to.
     * @param int $numberOfIndents The number of indents to be added to the file before it is written out to the yaml file.
     * @param string $parameterKey The key attached to the value (in the case it is a "yaml object").
     */
    private static function writeInlineValues ($parameterFollowUp, $pathToFile, $numberOfIndents, $parameterKey = "")
    {
        $outputString = "{ " . $parameterKey . ": " . $parameterFollowUp . " }\n";

        if (strlen($parameterKey) === 0) {
            $outputString = "- " . $parameterFollowUp . "\n";
        }

        self::$filesystem->appendToFile(
            $pathToFile,
            self::buildOutputString($outputString, $numberOfIndents)
        );
    }

    /**
     * Write out values that span multiple lines to the file (lists or multiple objects).
     *
     * @param array $parameterFollowUp The value to be written out to the file.
     * @param string $pathToFile The path to the file to be written to.
     * @param int $numberOfIndents The number of indents to be added to every written line of the file.
     * @param string $parameterKey The key attached to the value (is optional and can be empty, which signals a list).
     */
    private static function writeMultiLineValues(array $parameterFollowUp, $pathToFile, $numberOfIndents, $parameterKey = "")
    {
        if (strlen($parameterKey) > 0) {
            self::$filesystem->appendToFile(
                $pathToFile,
                self::buildOutputString($parameterKey . ":\n", $numberOfIndents)
            );
        }

        self::writeSubTree(
            $pathToFile,
            $parameterFollowUp,
            "",
            true,
            $numberOfIndents + 4
        );
    }

    /**
     * Adds various markings and elements of a typical yaml string to the given input in order to create a valid yaml string.
     *
     * @param string $input The string to be edited.
     * @param int $numberOfIndents The number of indents to be placed in front of the given line.
     * @param bool $isKey An optional boolean stating whether the given string is a key (and only a key).
     *
     * @return string Returns the formatted string.
     */
    private static function buildOutputString ($input, $numberOfIndents, $isKey = false)
    {
        if (!(strlen(trim($input)) > 0)) {
            return "";
        }

        $input =
            str_pad($input,$numberOfIndents+strlen($input), " ", STR_PAD_LEFT);

        if ($isKey) {
            return $input.":";
        }

        return $input;
    }
}
