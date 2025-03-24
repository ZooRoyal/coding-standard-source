<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\ObjectsCallisthenics;

use Nette\Utils\FileSystem;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Util\Tokens;

use function Safe\define;

final class FileFactory
{
    public function __construct()
    {
        // legacy compatibility
        if (! defined('PHP_CODESNIFFER_VERBOSITY')) {
            define('PHP_CODESNIFFER_VERBOSITY', 0);
            define('PHP_CODESNIFFER_CBF', false);
            define('PHP_CODESNIFFER_IN_TESTS', true);
        }

        // initialize Token constants
        if (! defined('T_NONE')) {
            define('_TOKENS_LOADED_FROM_CLASS', Tokens::class); //trigger autoload of Tokens class
        }
    }

    public function createFile(string $filePath): File
    {
        $config = new Config();
        $ruleset = new Ruleset($config);

        $file = new File($filePath, $ruleset, $config);
        $fileContent = FileSystem::read($filePath);
        $file->setContent($fileContent);
        $file->parse();

        return $file;
    }
}
