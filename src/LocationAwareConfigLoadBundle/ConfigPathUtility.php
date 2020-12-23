<?php


namespace CJW\CJWConfigProcessor\src\LocationAwareConfigLoadBundle;


use CJW\CJWConfigProcessor\src\Utility\Utility;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Yaml\Parser;

/**
 * Class ConfigPathUtility is used to provide all (static) functionality that is responsible for storing parameters and their paths.
 * And it is used to determine paths that lead to bundle configuration in order to find out where the parameters that are being used by
 * the bundles stem from.
 *
 * @package CJW\CJWLocationAwareConfigLoadBundle\src
 */
class ConfigPathUtility
{

    /**
     * @var string The assortment of file extensions which can be used to configure symfony.
     */
    private static $configExtensions = "";

    /**
     * @var array Stores all added configuration paths
     */
    private static $configPaths = [];

    /**
     * @var PhpFilesAdapter A cache for the routes that have been determined throughout the previous loading processes.
     */
    private static $configPathCache;

    /**
     * @var bool States whether it has been tried to retrieve the existing paths from the cache already / the cache has been initialised.
     */
    private static $cacheInitialized = false;

    /**
     * @var bool States whether there has been a change in paths (this only occurs through adding a path (for now)).
     */
    private static $pathsChanged = false;

    /**
     * @var bool This boolean states whether there has been a change to the paths that warrants the kernel and thereby load process to be restarted to include the newly found paths.
     */
    private static $restartLoadProcess = false;

    /**
     * @var string The directory in which to cache all the routes (in order to prevent the cache from being stored only temporarily)
     */
    private static $cacheDir = null;

    /**
     * Serves to set up and initialise all major internal attributes in order to allow the class to function properly.
     * It initiates the cache (if it hasn't already), retrieves the routes from the cache, parses the manually defined routes
     * and sets the internal boolean attributes to their initial value.
     */
    public static function initializePathUtility()
    {
        if (!self::$cacheInitialized) {
            // If the cache has not yet been instantiated
            if (!isset(self::$configPathCache)) {
                try {
                    self::$configPathCache = new PhpFilesAdapter("", 0, self::$cacheDir);
                } catch (CacheException $e) {
                    self::$configPathCache = new PhpFilesAdapter();
                }
            }

            self::$restartLoadProcess = false;
            self::$pathsChanged = false;

            try {
                // Retrieve the cached routes
                self::$configPaths =
                    Utility::cacheContractGetOrSet("cjw_config_paths", self::$configPathCache,
                        function () {
                            return [];
                        }
                    );

                self::$cacheInitialized = true;

                // Parse the manual path_config-file
                self::getUserDefinedPaths();
            } catch (InvalidArgumentException | Exception $e) {
                self::$configPaths = [];
            }
        }
    }

    /**
     * Takes a given path (which belongs to a bundle, most of the times to an Extension class in the DependencyInjection subdirectory) and
     * changes the path so that it points towards the bundle's config directory. This is based on Symfony's bundle conventions
     * and best practices (as the ExtensionClass is always present in the DependencyInjection directory and the config is
     * present under Resources/config **as of Symfony 5.1.5**)
     *
     * <br> But, paths which point to a file / directory which does not exist, are not added to the paths list.
     *
     * @param string $extensionPath The path pointing to a bundle's ExtensionClass.
     *
     * @return string|null Returns the converted string or null, if the path does not point to the DependencyInjection or a directory which does not exist.
     */
    public static function convertExtensionPathToConfigDirectory($extensionPath)
    {
        $configDirPath = preg_match("/\.php$/",$extensionPath)? dirname($extensionPath) : $extensionPath;

        if (preg_match("/\/$/", $configDirPath)) {
            $configDirPath = substr($configDirPath,0,strlen($configDirPath)-1);
        }

        while (!preg_match("/.*\/vendor\/?$/", $configDirPath)) {
            if (file_exists($configDirPath.DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config")) {
                $configDirPath = $configDirPath.DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR;
                break;
            } else if (file_exists($configDirPath.DIRECTORY_SEPARATOR."composer.json")) {
                break;
            }

            $configDirPath = dirname($configDirPath);
        }

        return preg_match("/\/$/", $configDirPath)? $configDirPath."*".self::$configExtensions : null;
    }

    /**
     * Takes a given path and adds that path to the internal path-list. Also signals internally,
     * that the path list has been changed.
     *
     * @param string $configPath The path to be added to the list.
     * @param bool $isGlobPattern An (optional) boolean stating whether the path is a glob-resource / pattern which will have to be loaded differently from non-glob-pattern.
     */
    public static function addPathToPathlist($configPath, $isGlobPattern = true)
    {
        // If the cache has not been initialised, initialise it.
        if (!self::$cacheInitialized) {
            self::initializePathUtility();
        }

        // Only if the path does not yet exist in the list, add it
        if (!empty($configPath) && !key_exists($configPath,self::$configPaths)) {
            self::$pathsChanged = true;
            self::$configPaths[$configPath] = $isGlobPattern;
        }
    }

    /**
     * Persists the paths that have been added during the load process in the cache. But only if there have been changes
     * to the internal paths, otherwise the already existing cached paths will not be overwritten.
     *
     * <br> Also signals, that a restart of the load process is useful / necessary.
     */
    public static function storePaths()
    {
        if (self::$cacheInitialized && self::$pathsChanged) {
            try {
                self::$configPathCache->deleteItem("cjw_config_paths");
                Utility::cacheContractGetOrSet("cjw_config_paths", self::$configPathCache,
                    function () {
                        return self::$configPaths;
                    }
                );
                self::$restartLoadProcess = true;
            } catch (InvalidArgumentException $e) {
            }
        }
    }

    /**
     * @return array Returns the paths that have been found and set internally.
     */
    public static function getConfigPaths()
    {
        return self::$configPaths;
    }

    /**
     * @return string Returns the config extensions.
     */
    public static function getConfigExtensions()
    {
        return self::$configExtensions;
    }

    /**
     * @return bool Returns a boolean, which states, whether the paths have changed internally.
     */
    public static function pathsChanged()
    {
        return self::$pathsChanged;
    }

    /**
     * @return bool Returns a boolean value that states whether the loading process is supposed to be restarted (due to newly added paths).
     */
    public static function isSupposedToRestart()
    {
        return self::$restartLoadProcess;
    }

    /**
     * Allows the configuration extensions which are valid in Symfony, to be set from the outside.
     *
     * @param string $configExtensions A string (glob-pattern) of extensions that are valid for symfony configuration.
     */
    public static function setConfigExtensions($configExtensions)
    {
        self::$configExtensions = $configExtensions;
    }

    /**
     * Sets the cache directory for this class of the bundle, based on the general cache path of the installation.
     *
     * @param string $cacheDir The blank, standard cache path of the project.
     */
    public static function setCacheDir($cacheDir)
    {
        if (is_dir($cacheDir)) {
            self::$cacheDir = $cacheDir."/cjw/config-processor-bundle/";
        }
    }

    /**
     * This function parses the paths defined by users of the bundle in the config_paths.yaml and adds them to the path
     * array.
     */
    private static function getUserDefinedPaths()
    {
        $parser = new Parser();

        // Go from this path to the config path of the bundle
        $pathToConfigRoutes = substr(__DIR__, 0, strpos(__DIR__, "src",-3)) . "Resources/config/config_paths.yaml";
        $userDefinedConfigPaths = $parser->parseFile($pathToConfigRoutes);

        // Are there even parameters set in the file? If not, then just initiate the variable as an empty array
        $configPaths =
            (is_array($userDefinedConfigPaths) && key_exists("parameters",$userDefinedConfigPaths))?
                $userDefinedConfigPaths["parameters"] : [];

        foreach ($configPaths as $pathName => $pathInfo) {
            // First check, whether some basic information is set for the defined routes (to see whether they can be worked with)
            if (self::checkUserDefinedPath($pathInfo)) {
                if ($pathInfo["addConfExt"]) {
                    $pathInfo["path"] .= self::$configExtensions;
                }
                self::addPathToPathlist($pathInfo["path"], $pathInfo["glob"]);
            }
        }
    }

    /**
     * Checks the user defined paths for any kind of errors with regards to the definition of said paths.
     *
     * @param array $path A path array (hopefully with 3 items under the keys of "path", "glob" and "addConfExt").
     *
     * @return bool Boolean which states whether the path at least passes the most basic checks regarding their structure.
     */
    private static function checkUserDefinedPath(array $path)
    {
        if (is_array($path) && count($path) === 3) {
            if (!(key_exists("path",$path) && is_string($path["path"]) && !empty($path["path"]))) {
                 return false;
            }

            if (!(key_exists("glob", $path) && is_bool($path["glob"]))) {
                return false;
            }

            if (!(key_exists("addConfExt",$path)) && is_bool($path["addConfExt"])) {
                return false;
            }

            return true;
        }

        return false;
    }
}
