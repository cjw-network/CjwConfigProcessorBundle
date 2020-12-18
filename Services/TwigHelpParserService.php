<?php


namespace CJW\CJWConfigProcessor\Services;


use CJW\CJWConfigProcessor\src\Utility\Parsedown;
use CJW\CJWConfigProcessor\src\Utility\Utility;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Class TwigHelpParserService is a twig helper class which adds a function to all twig templates to parse markdown
 * files into html representations via Parsedown and return the html to the template.
 *
 * <br>The files must follow a certain naming scheme for this service to work:
 * <feature-name>.<language>.[optionally more segments.<md>
 *
 * @package CJW\CJWConfigProcessor\Services
 */
class TwigHelpParserService extends AbstractExtension implements GlobalsInterface
{

    /**
     * @var Parsedown Instance of the class Parsedown in order to enable parsing markdown files.
     */
    private $parsedown;
    /**
     * @var string If no other language is given or found, search files for this fallback language.
     */
    private $fallBackLanguage;
    /**
     * @var string The path to the directory in which the help files are stored.
     */
    private $helpTextDirectory;
    /**
     * @var PhpFilesAdapter A cache adapter to allow caching the parsed markdown blocks.
     */
    private $cache;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->fallBackLanguage = "en";
        $helper = dirname(__FILE__);
        $serviceIndex = strpos($helper,"/Service");
        $this->helpTextDirectory = substr($helper,0,$serviceIndex)."/Resources/doc/help";
        $this->cache = new PhpFilesAdapter();
    }

    public function getGlobals()
    {
        return [];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                "getHelpText",
                array($this, "getHelpText"),
            ),
        ];
    }

    /**
     * Parses a markdown file from the help text directory that has been set for the class through the given name
     * and locale.
     *
     * @param string $fileName The name of the file / feature (**not a full path to it** and also not including any file extensions!!).
     * @param string $_locale The locale / language for which to search the file (will use fallback language, if nothing is found for the given language).
     *
     * @return string Returns the parsed markdown file as a string containing html.
     */
    public function getHelpText($fileName, $_locale)
    {
        $helpTextFiles = glob($this->helpTextDirectory."/*");

        $helpFileName = $fileName;

        foreach ($helpTextFiles as $helpTextFile) {
            $helpFileBasename = basename($helpTextFile);
            if (
                preg_match(
                    "/^".$fileName."\.".$_locale.".*\.md$/",
                    $helpFileBasename
                )
            ) {
                $helpFileName = $helpFileBasename;

                break;
            } else if (
                preg_match(
                    "/^".$fileName."\.".$this->fallBackLanguage.".*\.md$/",
                    $helpFileBasename
                )
            ) {
                $helpFileName = $helpFileBasename;
            }
        }

        if (is_file($this->helpTextDirectory."/".$helpFileName)) {
            return $this->parseFileContents($helpFileName);
        }

        return "<h1>No help file could be found for the current context.</h1>";
    }

    /**
     * Parses a given markdown file to html and returns the string containing the html.
     *
     * @param string $fileName The path to the file to parse.
     *
     * @return string The parsed output of the markdown file.
     *
     * @throws \Psr\Cache\InvalidArgumentException Throws a cache exception if a problem arises when trying to cache the parsed text.
     */
    private function parseFileContents ($fileName)
    {

        return Utility::cacheContractGetOrSet($fileName,$this->cache,
            function() use ($fileName) {
                return $this->parsedown->text(
                    file_get_contents(
                        $this->helpTextDirectory."/".$fileName
                    )
                );
            }
        );
    }
}
