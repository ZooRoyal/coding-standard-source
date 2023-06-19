<?php

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

class PHPPsalmStubsFilesGenerator
{

    private const STUBS_FILES_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
      <stubs xmlns="https://getpsalm.org/schema/config">
        %1$s
     </stubs>';

    private string $phpStubsFilesPath;

    public function __construct(
        private readonly Filesystem  $filesystem,
        private readonly Environment $environment
    )
    {
        $this->phpStubsFilesPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/psalm/stubs-files.xml';
    }

    /**
     * Writes a custom config file just in time for PHPStan to read.
     *
     * @param array<EnhancedFileInfo> $excludesFiles
     * @param array<string, array<int,string>> $functionsFiles
     */
    public function writeStubsFilesFile(OutputInterface $output, array $functionsFiles): void
    {
        $output->writeln(
            '<info>Writing new Include-file stubs-files for Psalm configuration.</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
        $generateStubsFilesNodes = $this->addStubsFilesNode($output, $functionsFiles);
        $content = sprintf(self::STUBS_FILES_TEMPLATE, $generateStubsFilesNodes);
        $this->filesystem->dumpFile($this->phpStubsFilesPath, $content);
    }

    /**
     * Adds the functions files of several composer packages to the PHPStan Autoloader. PHPStan will complain about
     * unknown functions if this should fail.
     *
     * @param array<string, array<int,string>> $functionsFiles
     *
     */
    private function addStubsFilesNode(OutputInterface $output, array $functionsFiles): string
    {

        $content = '';
        foreach ($functionsFiles as $tool => $functionsFile) {
            $toolPath = $this->environment->getRootDirectory()->getRealPath() . '/vendor/' . $tool;
            if (!$this->filesystem->exists($toolPath)) {
                $output->writeln(
                    '<info>' . $tool . ' not found. Skip loading ' . implode(', ', $functionsFile) . '.</info>',
                    OutputInterface::VERBOSITY_VERBOSE,
                );
                continue;
            }

            $content .= array_reduce(
                $functionsFile,
                fn(string $carry, $item) => '<file name="' . $toolPath. $item . '" />' . PHP_EOL,
                '');

        }
        return $content;
    }
}
