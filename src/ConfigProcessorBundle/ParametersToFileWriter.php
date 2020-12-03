<?php


namespace CJW\CJWConfigProcessor\src\ConfigProcessorBundle;


use DateTime;
use Symfony\Component\Filesystem\Filesystem;

class ParametersToFileWriter
{
    /** @var bool  */
    private static $initialized = false;

    /** @var Filesystem  */
    private static $filesystem;

    public static function initializeFileWriter () {
        if (!self::$initialized) {

            if (!self::$filesystem) {
                self::$filesystem = new Filesystem();
            }
        }
    }

    public static function writeParametersToFile (
        array $parametersToWrite,
        string $downloadDescriptor = null
    ): string {

        if (!self::$initialized) {
            self::initializeFileWriter();
        }

        $temporaryFile = self::$filesystem->tempnam(sys_get_temp_dir(),"parameter_list_", ".yaml");

        $tmpDir = pathinfo($temporaryFile,PATHINFO_DIRNAME);
        $currentDate = new DateTime();
        $currentDate = $currentDate->format("Y-m-d_H.i");
        $downloadDescriptor = $downloadDescriptor?? "all_parameters";
        $targetName = $tmpDir."/parameter_list_".$downloadDescriptor."_".$currentDate.".yaml";

        if (!file_exists($targetName)) {
            if ($temporaryFile) {
                $siteAccess = $downloadDescriptor === "favourites"? null : $downloadDescriptor;
                self::appendDataPerKey($temporaryFile,$parametersToWrite, $siteAccess);
            }

            self::$filesystem->rename($temporaryFile,$targetName);
        }

        return $targetName;
    }

    private static function appendDataPerKey (
        string $pathToFileToWriteTo,
        array $parametersToWrite,
        string $siteAccess = null
    ) {
        self::$filesystem->appendToFile($pathToFileToWriteTo,"parameters:\n");

        foreach (array_keys($parametersToWrite) as $key) {
            self::$filesystem->appendToFile($pathToFileToWriteTo,"\n");
            $keyDisplay = $key;

            if ($siteAccess) {
                $keyDisplay .= ".".$siteAccess;
            }

            self::writeSubTree($pathToFileToWriteTo, $parametersToWrite[$key],$keyDisplay);
        }
    }

    private static function writeSubTree (
        string $pathToFileToWriteTo,
        array $subTreeToWrite,
        string $previousKey,
        bool $valueReached = false,
        int $numberOfIndents = 0
    ) {
        foreach ($subTreeToWrite as $parameterKey => $parameterFollowUp) {
            $parameterFollowUpIsArray = is_array($parameterFollowUp);

            if (!$parameterFollowUpIsArray) {
                if (is_bool($parameterFollowUp)) {
                    $parameterFollowUp = $parameterFollowUp? "true" : "false";
                } else {
                    $parameterFollowUp = '"'.$parameterFollowUp.'"';
                }
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

    private static function writeSingleLineKeys (
        string $parameterKey,
        string $previousKey,
        string $output,
        string $pathToWriteTo
    ): void
    {
        $fileInput = $previousKey . ": " . $output . "\n";

        if (!$parameterKey === "parameter_value") {
            $parameterKey = $previousKey . "." . $parameterKey;
            $fileInput = $parameterKey. ":\n";
        }

        if ($output) {
            self::$filesystem->appendToFile(
                $pathToWriteTo,
                self::buildOutputString(
                    $fileInput,
                    4
                )
            );
        }
    }

    private static function writeMultiLineKeys (
        string $parameterKey,
        string $previousKey,
        array $output,
        string $pathToWriteTo
    ): void
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

    private static function writeInlineValues (
        string $parameterFollowUp,
        string $pathToFile,
        int $numberOfIndents,
        string $parameterKey = ""
    ): void
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

    private static function writeMultiLineValues(
        array $parameterFollowUp,
        string $pathToFile,
        int $numberOfIndents,
        string $parameterKey = ""
    ): void
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

    private static function buildOutputString (
        string $input,
        int $numberOfIndents,
        bool $isKey = false
    ): string {
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
