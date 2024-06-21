<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\ExclusionListFactory;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;

class PHPStanConfigGenerator
{
    private const TOOL_FUNCTIONS_FILE_MAPPING
        = [
            'deployer/deployer' => ['/src/functions.php'],
            'hamcrest/hamcrest-php' => ['/hamcrest/Hamcrest.php'],
            'mockery/mockery' => ['/library/helpers.php'],
            'sebastianknott/hamcrest-object-accessor' => ['/src/functions.php'],
        ];
    private const STATIC_DIRECTORIES_TO_SCAN
        = [
            '/Plugins',
            '/custom/plugins',
            '/custom/project',
            '/vendor',
            '/vendor-bin',
        ];
    private const MAX_TMP_RANDOM = 1000000;
    private const MIN_TMP_RANDOM = 0;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Environment $environment,
        private readonly PhpVersionConverter $phpVersionConverter,
        private readonly OutputInterface $output,
        private readonly ComposerInterpreter $composerInterpreter,
        private readonly ExclusionListFactory $exclusionListFactory,
    ) {
    }

    /**
     * This method adds dynamic config values to the given config array. This is needed for the PHPStan config drop-in.
     *
     * @param array<string,array<int|string,array<int,string>|string>|string> $configValues
     *
     * @return array<string,array<int|string,array<int,string>|string>|string>
     */
    public function addDynamicConfigValues(array $configValues): array
    {
        $configValues = $this->addFunctionsFiles($configValues);
        $configValues = $this->addExcludedFiles($configValues);
        $configValues = $this->addPhpVersion($configValues);
        $configValues = $this->addStaticDirectoriesToScan($configValues);
        $configValues = $this->addRandomTempDir($configValues);
        return $configValues;
    }

    /**
     * Adds the functions files of several composer packages to the PHPStan Autoloader. PHPStan will complain about
     * unknown functions if this should fail.
     *
     * @param array<string,array<string>> $configValues
     *
     * @return array<string,array<string|int,array<string>>>
     */
    private function addFunctionsFiles(array $configValues): array
    {
        foreach (self::TOOL_FUNCTIONS_FILE_MAPPING as $tool => $functionsFiles) {
            $toolPath = $this->environment->getRootDirectory()->getRealPath() . '/vendor/' . $tool;
            if (!$this->filesystem->exists($toolPath)) {
                $this->output->writeln(
                    '<info>' . $tool . ' not found. Skip loading ' . implode(', ', $functionsFiles) . '.</info>',
                    OutputInterface::VERBOSITY_VERBOSE,
                );
                continue;
            }
            foreach ($functionsFiles as $functionsFile) {
                $configValues['parameters']['scanFiles'][] = $toolPath . $functionsFile;
            }
        }
        return $configValues;
    }

    /**
     * Adds the list of files to be excluded to the config.
     *
     * @param array<string,array<string|int,string|array<string>>> $configValues
     *
     * @return array<string,array<array<string|int,string>>>
     */
    private function addExcludedFiles(array $configValues): array
    {
        $exclusionList = $this->exclusionListFactory->build(PHPStanCommand::EXCLUSION_LIST_TOKEN);
        $directoryExcludedFilesStrings = array_map(
            static fn(EnhancedFileInfo $file): string => $file->getRealPath(),
            $exclusionList,
        );
        $configValues['parameters']['excludePaths']['analyseAndScan'] = $directoryExcludedFilesStrings;
        return $configValues;
    }

    /**
     * Adds the list of static folders to scan if they exist.
     *
     * @param array<string,array<string|int,string|array<string>>> $configValues
     *
     * @return array<string,array<string|int,array<string>>>
     */
    private function addStaticDirectoriesToScan(array $configValues): array
    {
        foreach (self::STATIC_DIRECTORIES_TO_SCAN as $directory) {
            $absolutePath = $this->environment->getRootDirectory()->getRealPath() . $directory;
            if (!$this->filesystem->exists($absolutePath)) {
                continue;
            }

            $configValues['parameters']['scanDirectories'][] = $absolutePath;
        }

        return $configValues;
    }

    /**
     * Adds the phpVersion parameter to the config.
     *
     * @param array<string,array<string|int,string|array<string>>> $configValues
     *
     * @return array<string,array<string|int,string|array<string>>>
     */
    private function addPhpVersion(array $configValues): array
    {
        $phpVersion = $this->composerInterpreter->getMinimalViablePhpVersion();
        $result = $this->phpVersionConverter->convertSemVerToPhpString($phpVersion);
        $configValues['parameters']['phpVersion'] = $result;

        return $configValues;
    }

    /**
     * This method adds a random temp directory to the config. This is necessary to
     * reevaluate the config on every run. If this does not happen, PHPStan will cache
     * the config and not reevaluate it, which prevents the dynamic config values from
     * being applied.
     *
     * @param array<string,array<string|int,string|array<string>>> $configValues
     *
     * @return array<string,array<string|int,string|array<string>>>
     */
    private function addRandomTempDir(array $configValues): array
    {
        $configValues['parameters']['tmpDir'] = sys_get_temp_dir()
            . '/phpstan'
            . random_int(self::MIN_TMP_RANDOM, self::MAX_TMP_RANDOM);

        return $configValues;
    }
}
