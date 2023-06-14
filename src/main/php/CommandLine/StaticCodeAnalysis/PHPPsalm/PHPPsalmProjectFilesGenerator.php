<?php

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

class PHPPsalmProjectFilesGenerator
{

    private const PROJECT_FILES_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
      <projectFiles xmlns="https://getpsalm.org/schema/config">
        %1$s
        <ignoreFiles>
            %2$s
        </ignoreFiles>
     </projectFiles>';


    private string $phpProjectFilesPath;

    public function __construct(
        private readonly Filesystem  $filesystem,
        private readonly Environment $environment
    )
    {
        $this->phpProjectFilesPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/psalm/project-files.xml';
    }

    /**
     * Writes a custom config file just in time for PHPStan to read.
     *
     * @param array<EnhancedFileInfo> $excludesFiles
     * @param array<EnhancedFileInfo>|null $targetedFiles
     */
    public function writeProjectFilesFile(OutputInterface $output, array $excludesFiles, ?array $targetedFiles): void
    {
        $output->writeln(
            '<info>Writing new Include-file project-files for Psalm configuration.</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $targetedFilesNodes = $this->generateTargetedFilesNodes($targetedFiles);
        $generateExcludesFilesNodes = $this->generateExcludesFilesNodes($excludesFiles);
        $content = sprintf(self::PROJECT_FILES_TEMPLATE, $targetedFilesNodes, $generateExcludesFilesNodes);
        $this->filesystem->dumpFile($this->phpProjectFilesPath, $content);
    }

    /**
     * Adds function bootstraps to PHPStan config so imported functions won't show up as unknown.
     *
     * @param array<EnhancedFileInfo>|null $targetedFiles
     *
     */
    private function generateTargetedFilesNodes(?array $targetedFiles): string
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


    /**
     * Adds the list of files to be excluded to the config.
     *
     * @param array<EnhancedFileInfo> $excludesFiles
     *
     */
    private function generateExcludesFilesNodes(array $excludesFiles): string
    {
        $excludedFilesXMLString = '';
        $directoryExcludedFilesStrings = array_map(
            static fn(EnhancedFileInfo $file): string => $file->getRelativePathname(),
            $excludesFiles,
        );

        foreach ($directoryExcludedFilesStrings as $excludesFile) {
            $excludedFilesXMLString .= '<directory name="' . $excludesFile . '" />' . PHP_EOL;
        }

        return $excludedFilesXMLString;
    }
}
