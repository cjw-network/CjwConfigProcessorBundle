<?php


namespace App\CJW\ConfigProcessorBundle\src;


use Symfony\Component\Filesystem\Filesystem;

class ParametersToFileWriter
{

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

    public static function writeParametersToFile (array $parametersToWrite): string {
        if (!self::$initialized) {
            self::initializeFileWriter();
        }

        $temporaryFile = self::$filesystem->tempnam(sys_get_temp_dir(),"parameter_list_", ".yaml");

        if ($temporaryFile) {
            self::appendDataPerKey($temporaryFile,$parametersToWrite);
        }

        return $temporaryFile;
    }

    private static function appendDataPerKey (string $pathToFileToWriteTo,array $parametersToWrite) {
        self::$filesystem->appendToFile($pathToFileToWriteTo,"parameters:\n");

        foreach (array_keys($parametersToWrite) as $key) {
            self::$filesystem->appendToFile($pathToFileToWriteTo,"\n");
            self::writeSubTree($pathToFileToWriteTo, $parametersToWrite[$key],$key);
        }
    }

    private static function writeSubTree (string $pathToFileToWriteTo, array $subTreeToWrite, string $previousKey, bool $valueReached = false, int $numberOfIndents = 0) {
        foreach ($subTreeToWrite as $parameterKey => $parameterFollowUp) {
            $parameterFollowUp = is_string($parameterFollowUp) ? '"' . $parameterFollowUp . '"' : $parameterFollowUp;
            $parameterFollowUpIsArray = $parameterFollowUp && is_array($parameterFollowUp);

            if (!$valueReached) {
                if ($parameterKey !== "parameter_value") {
                    $parameterKey = $previousKey . "." . $parameterKey;

                    if ($parameterFollowUpIsArray) {
                        self::writeSubTree($pathToFileToWriteTo, $parameterFollowUp, $parameterKey);
                    } else if (!$parameterFollowUp) {
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString($parameterKey . ":\n", 4));
                    }
                }

                if ($parameterKey === "parameter_value") {
                    $valueReached = true;

                    if ($parameterFollowUpIsArray) {
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString($previousKey . ":\n", 4));
                        self::writeSubTree($pathToFileToWriteTo, $parameterFollowUp, $previousKey, $valueReached, 8);
                    } else if ($parameterFollowUp) {
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString($previousKey . ": " . $parameterFollowUp . "\n", 4));
                    }
                }
            } else {
                if (is_numeric($parameterKey)) {

                    if ($parameterFollowUpIsArray) {
                        self::writeSubTree($pathToFileToWriteTo, $parameterFollowUp, "", $valueReached, $numberOfIndents + 4);
                    } else {
                        $parameterFollowUp = "- " . $parameterFollowUp . "\n";
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString($parameterFollowUp, $numberOfIndents));
                    }

                } else {
                    if ($parameterFollowUpIsArray) {
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString($parameterKey . ":\n", $numberOfIndents));
                        self::writeSubTree($pathToFileToWriteTo, $parameterFollowUp, "", $valueReached, $numberOfIndents + 4);
                    } else if (!is_array($parameterFollowUp)) {
                        self::$filesystem->appendToFile($pathToFileToWriteTo, self::buildOutputString("{ ".$parameterKey . ": " . $parameterFollowUp . " }\n", $numberOfIndents));
                    }
                }
            }
        }

//        foreach ($subTreeToWrite as $parameterKey => $parameterFollowUp) {
//
//            if ($parameterKey !== "parameter_value" && !is_numeric($parameterKey)) {
//                $outputString = self::buildOutputString($parameterKey, $numberOfIndents);
//                self::$filesystem->appendToFile($pathToFileToWriteTo, $outputString);
//            }
//
//            if ($parameterFollowUp && is_array($parameterFollowUp)) {
//                self::writeSubTree($pathToFileToWriteTo,$parameterFollowUp,$numberOfIndents+4);
//            } else if (!is_array($parameterFollowUp)) {
//                if (is_numeric($parameterKey)) {
//                    $parameterFollowUp = "- ".$parameterFollowUp;
//                    $parameterFollowUp = self::buildOutputString($parameterFollowUp, $numberOfIndents, false);
//                }
//
//                self::$filesystem->appendToFile($pathToFileToWriteTo," ".$parameterFollowUp);
//            }
//        }
    }

    private static function buildOutputString (string $input, int $numberOfIndents, bool $isKey = false): string {
        if (!(strlen(trim($input)) > 0)) {
            return "";
        }

        $input = str_pad($input,$numberOfIndents+strlen($input), " ", STR_PAD_LEFT);

        if ($isKey) {
            return $input.":";
        }

        return $input;
    }

}
