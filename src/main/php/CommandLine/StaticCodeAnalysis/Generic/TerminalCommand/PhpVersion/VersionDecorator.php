<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Composer\Semver\Semver;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecorator extends TerminalCommandDecorator
{
    /** @var array<string> */
    private array $phpVersions = [];
    private ?string $cachedMinPhpVersion = null;

    public function __construct(
        private readonly Environment $environment,
        private readonly FileSearchInterface $fileSearchInterface,
        private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory,
    ) {
        $phpVersionRanges = [
            '7.4.' => '33',
            '8.0.' => '27',
            '8.1.' => '17',
            '8.2.' => (explode('.', phpversion()))[2],
        ];

        foreach ($phpVersionRanges as $phpVersionString => $phpMaxPatchVersion) {
            $phpPatchLevels = range('0', $phpMaxPatchVersion);
            foreach ($phpPatchLevels as $phpPatchLevel) {
                $this->phpVersions[] = $phpVersionString . $phpPatchLevel;
            }
        }
    }

    public function decorate(DecorateEvent $event): void
    {
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof VersionDependentTerminalCommand) {
            return;
        }

        if ($this->cachedMinPhpVersion !== null) {
            $terminalCommand->setPhpVersion($this->cachedMinPhpVersion);
            return;
        }

        $composerFiles = $this->gatherComposerFiles();
        $minPhpVersion = $this->searchMinimalViablePhpVersion($composerFiles);

        $this->cachedMinPhpVersion = $minPhpVersion;

        $terminalCommand->setPhpVersion($minPhpVersion);

        $event->getOutput()->writeln(
            '<info>Targeted PHP version is ' . $minPhpVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
    }

    /**
     * Check if $phpVersionConstraint is a version number and return it or if we find a php version that satisfies
     * the constraint.
     */
    private function extractActualPhpVersion(string $phpVersionConstraint): string
    {
        if (preg_match('/^(\d+)(\.\d)?(\.\d)?$/', $phpVersionConstraint, $matches)) {
            return $matches[1] . ($matches[2] ?? '.0') . ($matches[3] ?? '.0');
        }

        $minPhpVersion = '7.4.0';
        foreach ($this->phpVersions as $phpVersion) {
            if (SemVer::satisfies($phpVersion, $phpVersionConstraint)) {
                $minPhpVersion = $phpVersion;
                break;
            }
        }
        return $minPhpVersion;
    }

    /**
     * Finds all composer files in the project.
     *
     * @return array<EnhancedFileInfo>
     */
    private function gatherComposerFiles(): array
    {
        $rootDirectory = $this->environment->getRootDirectory();
        $path = $rootDirectory->getRealPath();
        $composerFiles[] = $this->enhancedFileInfoFactory->buildFromPath($path . '/composer.json');

        $foundComposerFiles = $this->fileSearchInterface->listFolderFiles(
            fileName: 'composer.json',
            path: $rootDirectory,
            minDepth: 1,
            maxDepth: 4
        );

        $composerFiles = [...$composerFiles, ...$foundComposerFiles];
        return $composerFiles;
    }

    /**
     * Combines Constraints of given composer files and returns the lowest possible php version.
     *
     * @param array<EnhancedFileInfo> $composerFiles
     */
    private function searchMinimalViablePhpVersion(array $composerFiles): string
    {
        $minPhpVersion = '7.4.0';

        foreach ($composerFiles as $key => $composerFile) {
            $contents = file_get_contents($composerFile->getRealPath());
            $composerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // The first file is the root composer file, so we need to check the platform config.
            if ($key === 0) {
                $phpVersionConstraint = $composerConfig['config']['platform']['php']
                    ?? $composerConfig['require']['php']
                    ?? '*';
            } else {
                $phpVersionConstraint = $composerConfig['require']['php'] ?? '*';
            }

            $minPhpVersionPackage = $this->extractActualPhpVersion($phpVersionConstraint);
            $minPhpVersion = version_compare($minPhpVersion, $minPhpVersionPackage, '<')
                ? $minPhpVersionPackage
                : $minPhpVersion;
        }
        return $minPhpVersion;
    }
}
