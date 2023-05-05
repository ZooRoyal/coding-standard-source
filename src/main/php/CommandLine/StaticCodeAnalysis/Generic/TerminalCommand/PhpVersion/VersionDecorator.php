<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Composer\Semver\Semver;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecorator extends TerminalCommandDecorator
{
    /** @var array<string> */
    private array $phpVersions = [];

    public function __construct(
        private readonly Environment $environment,
        private readonly FileSearchInterface $fileSearchInterface,
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

        $path = $this->environment->getRootDirectory()->getRealPath();

        $contents = file_get_contents($path . '/composer.json');
        $composerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $initialPhpVersionConstraint = $composerConfig['config']['platform']['php']
            ?? $composerConfig['require']['php']
            ?? '*';

        $composerFiles = $this->fileSearchInterface->listFolderFiles(
            fileName: 'composer.json',
            path: $this->environment->getRootDirectory(),
            maxDepth: 4
        );

        $phpVersionConstraintComplete = '( ' . $initialPhpVersionConstraint . ' )';
        foreach ($composerFiles as $composerFile) {
            $contents = file_get_contents($composerFile->getRealPath());
            $composerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $phpVersionConstraint = $composerConfig['require']['php'] ?? '*';
            $phpVersionConstraintComplete .= ' ( ' . $phpVersionConstraint . ' )';
        }

        $minPhpVersion = $this->extractActualPhpVersion($phpVersionConstraintComplete);

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
        if (preg_match('/^\d(\.\d){0,2}$/', $phpVersionConstraint)) {
            return $phpVersionConstraint;
        }

        $minPhpVersion = '7.4';
        foreach ($this->phpVersions as $phpVersion) {
            if (SemVer::satisfies($phpVersion, $phpVersionConstraint)) {
                $minPhpVersion = $phpVersion;
                break;
            }
        }
        return $minPhpVersion;
    }
}
