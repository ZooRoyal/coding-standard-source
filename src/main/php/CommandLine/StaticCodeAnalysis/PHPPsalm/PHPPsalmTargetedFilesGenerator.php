<?php

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

class PHPPsalmTargetedFilesGenerator
{

    private const TARGETED_FILES_HEADER = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;

    private string $phpTargetedFilesPath;

    public function __construct(
        private readonly Filesystem  $filesystem,
        private readonly Environment $environment
    )
    {
        $this->phpTargetedFilesPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/psalm/targeted-files.xml';
    }

    /**
     * Exposes the path where the config file will be found to the world.
     */
    public function getTargetedFilesPath(): string
    {
        return $this->phpTargetedFilesPath;
    }

    /**
     * Writes a custom config file just in time for PHPStan to read.
     *
     * @param array<EnhancedFileInfo> $targetedList
     */
    public function writeTargetedFilesFile(OutputInterface $output, ?array $targetedList): void
    {
        $output->writeln(
            '<info>Writing new Include-file targeted-files for Psalm configuration.</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $directoriesAsString = $this->generateTargetedFilesConfig($targetedList);

        $content = self::TARGETED_FILES_HEADER . $directoriesAsString;

        $this->filesystem->dumpFile($this->phpTargetedFilesPath, $content);
    }

    /**
     * Adds function bootstraps to PHPStan config so imported functions won't show up as unknown.
     *
     * @param array<EnhancedFileInfo> $exclusionList
     *
     */
    private function generateTargetedFilesConfig(?array $targetedFiles): string
    {
        if ($targetedFiles === null) {
            $content = '<directory name="." />';
        } else {
            $content = array_reduce(
                $targetedFiles,
                fn(string $carry, EnhancedFileInfo $item) => '<directory name="' . $item->getRelativePathname() . '" />' . PHP_EOL,
                '');
        }

        return $content;
    }


}
