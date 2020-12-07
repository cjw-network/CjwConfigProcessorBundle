<?php


namespace CJW\CJWConfigProcessor\Services;


use CJW\CJWConfigProcessor\src\Utility\Parsedown;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class TwigHelpParserService extends AbstractExtension implements GlobalsInterface
{

    private $parsedown;
    private $fallBackLanguage;
    private $helpTextDirectory;
    private $cache;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->fallBackLanguage = "en";
        $helper = new ReflectionClass($this);
        $this->helpTextDirectory = $helper->getFileName();
        $serviceIndex = strpos($this->helpTextDirectory,"/Service");
        $this->helpTextDirectory = substr($this->helpTextDirectory,0,$serviceIndex)."/Resources/doc/help";
        $this->cache = new PhpFilesAdapter();
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                "getHelpText",
                array($this, "getHelpText"),
            ),
        ];
    }

    public function getHelpText(string $currentContext, string $_locale): string
    {
        $helpTextFiles = glob($this->helpTextDirectory."/*");

        $helpFileName = $currentContext;

        foreach ($helpTextFiles as $helpTextFile) {
            $helpFileBasename = basename($helpTextFile);
            if (
                preg_match(
                    "/^".$currentContext."\.".$_locale.".*\.md$/",
                    $helpFileBasename
                )
            ) {
                $helpFileName = $helpFileBasename;

                break;
            } else if (
                preg_match(
                    "/^".$currentContext."\.".$this->fallBackLanguage.".*\.md$/",
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

    private function parseFileContents (string $fileName): string
    {
        return $this->cache->get($fileName, function() use ($fileName) {

            return $this->parsedown->text(
                file_get_contents(
                    $this->helpTextDirectory."/".$fileName
                )
            );
        });
    }
}
