<?php

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

class PHPPsalmExcludedFilesGenerator
{
    private const STATIC_DIRECTORIES_TO_SCAN
        = [
            '/Plugins',
            '/custom/plugins',
            '/custom/project',
            '/vendor',
            '/vendor-bin',
        ];

    private const EXCLUDED_FILES_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>';

    private string $phpExcludedFilesPath;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Environment $environment
    ) {
        $this->phpExcludedFilesPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/psalm/excluded-files.xml';
    }

    /**
     * Exposes the path where the config file will be found to the world.
     */
    public function getExcludedFilesPath(): string
    {
        return $this->phpExcludedFilesPath;
    }

    /**
     * Writes a custom config file just in time for PHPStan to read.
     *
     * @param array<EnhancedFileInfo> $exclusionList
     */
    public function writeExcludedFilesFile(OutputInterface $output, array $exclusionList): void
    {
        $output->writeln(
            '<info>Writing new Include-file excluded-files for Psalm configuration.</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $configContentXML = $this->generateExcludedFilesConfig($exclusionList);

        $this->filesystem->dumpFile($this->phpExcludedFilesPath, $configContentXML);
    }

    /**
     * Adds function bootstraps to PHPStan config so imported functions won't show up as unknown.
     *
     * @param array<EnhancedFileInfo> $exclusionList
     *
     */
    private function generateExcludedFilesConfig(array $exclusionList): string
    {

        $excludedFilesXMLString = $this->addExcludedFiles($exclusionList);
        $content = str_replace(
            '<directory name="vendor" />',
            $excludedFilesXMLString,
            self::EXCLUDED_FILES_TEMPLATE,
        );

        return $content;
    }


    /**
     * Adds the list of files to be excluded to the config.
     *
     * @param array<EnhancedFileInfo>                              $exclusionList
     *
     */
    private function addExcludedFiles(array $exclusionList): string
    {
        $excludedFilesXMLString = '';
        $directoryExcludedFilesStrings = array_map(
            static fn(EnhancedFileInfo $file): string => $file->getRelativePathname(),
            $exclusionList,
        );

        foreach (self::STATIC_DIRECTORIES_TO_SCAN as $directory) {
            if (!$this->filesystem->exists($directory)) {
                continue;
            }
            $directoryExcludedFilesStrings[] = '.'.$directory;
        }

        foreach ($directoryExcludedFilesStrings AS $exludeFile) {
            $excludedFilesXMLString .= '<directory name="' . $exludeFile . '" />'. PHP_EOL;
        }

        return $excludedFilesXMLString;
    }

}
