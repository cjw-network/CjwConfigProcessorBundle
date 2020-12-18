<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


/**
 * Class CustomValueStorage is a static class used to keep track of all parameters and their paths being added
 * to the parameterBag of the internal configuration container. It provides functionality to both add parameters,
 * including their path and their values, and retrieve information about these parameters.
 *
 * @package CJW\CJWLocationAwareConfigLoadBundle\src
 */
class CustomValueStorage
{

    /**
     * @var array This is an array of all locations that have been encountered during the loading process.
     */
    private static $encounteredLocations = [];

    /**
     * @var array An array of all parameters being loaded by the config loading process and the values accompanied by the paths they stem from.
     */
    private static $parameterAndTheirLocations = [];

    /**
     * @var bool States whether new parameter values or paths can be added to the internal arrays or not.
     */
    private static $allowWrite = true;

    /**
     * @var bool States whether the bundle config loading process has begun.
     */
    private static $bundleConfig = false;

    /**
     * Adds a given parameter, including its value and its path, to an internal storage (an array) which then allows
     * tracking the path history of the parameter throughout the kernel-boot- and configuration-load-process.
     *
     * <br>It does not store a parameter twice as a key in the array but instead adds a new path and value
     * to the existing entry of the parameter. That allows a complete history of the parameter to be formed without
     * the parameter or value being overwritten.
     *
     * <br> Should there be a different value from the same file / path, the value in the internal array is simply being
     * overwritten and the first value is **not** stored separately in the array.
     *
     * @param string $parameterName The name of the parameter to add. This serves as a key for the then following entries into the array.
     * @param mixed $value The value attached to both the parametername and then the given path as well. It is going to be added under path-key as an entry of the array.
     * @param string $path The path (the origin) of the parameter value that is being set. It serves as a key under the parameter-key of the array.
     */
    public static function addParameterOrLocation($parameterName, $value, $path)
    {
        // Only if it is currently allowed to write, will the process even begin
        if (self::$allowWrite) {
            if (!in_array($path, self::$encounteredLocations)) {
                array_push(self::$encounteredLocations, $path);
            }

            if (!isset(self::$parameterAndTheirLocations[$parameterName])) {
                self::$parameterAndTheirLocations[$parameterName] = [$path => $value];
            } else if (
                self::$bundleConfig &&
                end(self::$parameterAndTheirLocations[$parameterName]) === $value
            ) {
                return;
            } else {
                self::$parameterAndTheirLocations[$parameterName][$path] = $value;
            }
        }
    }

    /**
     * This function serves to prohibit any write-processes to the internal parameter-and-path storage.
     * It simply sets an internal boolean which then prohibits any parameters or paths / values to be set.
     * In order to unlock the writing process, use {@see unlockParameters()}.
     */
    public static function lockParameters()
    {
        self::$allowWrite = false;
    }

    /**
     * Does the opposite of {@see lockParameters()} and instead unlocks the writing process of parameters
     * to the internal parameter-and-path storage. It sets the internal boolean to a different value
     * than {@see lockParameters()}.
     */
    public static function unlockParameters()
    {
        self::$allowWrite = true;
    }

    /**
     * This function serves to set the bundle-configuration-mode in {@see $this} class. If activated, unchanged parameter
     * values will not be set, as during the bundle configuration phase (as of Symfony 5.1.5), activated through the {@see MergeExtensionConfigurationPass},
     * all bundles are given a complete version of the parameterbag onto which they add their own configuration, but since
     * the containers are then merged in the pass, unchanged and untouched parameters are added again and again to the bag,
     * which would cause as many entrances into {@see $this} classes storages as there are bundles being run through.
     *
     * <br> **If you do not wish these duplicate entries, use this function and set the value to true. Otherwise, you can simply
     * ignore it**
     *
     * @param bool $activate A boolean stating that the mode is either to be active (true) or not (false).
     */
    public static function activateBundleConfigMode($activate)
    {
        self::$bundleConfig = $activate;
    }

    /**
     * Resets all internal states of the class. It removes all stored parameters and paths and also resets the
     * bundleConfigMode and the lock-status of the class internally. This serves to allow a "fresh" start with the internal
     * storage.
     */
    public static function reset()
    {
        self::$parameterAndTheirLocations = [];
        self::$bundleConfig = false;
        self::$allowWrite = true;
        self::$encounteredLocations = [];
    }

    /**
     * Returns the internally set parameters and the locations / values the parameters have been set from / to.
     *
     * @return array Returns an array of parameters as super-keys, the locations as sub-keys and the values found at the paths as entries.
     */
    public static function getParametersAndTheirLocations()
    {
        ksort(self::$parameterAndTheirLocations,SORT_STRING);
        return self::$parameterAndTheirLocations;
    }

    /**
     * Returns the internal array which keeps track of all encountered locations without any connection to
     * the parameters, values or other information. It resembles a plain "stack" of locations.
     *
     * @return array Returns an array which is filled with all encountered locations during the configuration-loading-process.
     */
    public static function getEncounteredLocations()
    {
        return self::$encounteredLocations;
    }

    /**
     * Allows a specific parameter to be retrieved from the internal storage of the class.
     *
     * @param string $parameterName The name of the parameter as string.
     *
     * @return array|null Returns the internal array with `$path => $value` pairs or null if the parameter is not present in the internal array.
     */
    public static function getLocationsForSpecificParameter($parameterName)
    {
        // Only if that parameter exists as a key in the array, will that parameters paths and values be returned, otherwise null
        return isset(self::$parameterAndTheirLocations[$parameterName]) ?
            self::$parameterAndTheirLocations[$parameterName] : null;
    }
}
