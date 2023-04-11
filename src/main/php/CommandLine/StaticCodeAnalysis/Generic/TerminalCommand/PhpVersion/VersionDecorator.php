<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Composer\Semver\Semver;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;


class VersionDecorator extends TerminalCommandDecorator
{
    /** @var array<string> */
    private array $phpVersions = ['7.4', '8.0', '8.1', '8.2'];

    public function __construct(private readonly Environment $environment)
    {
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
        $phpVersionConstraint = $composerConfig['config']['platform']['php']
            ?? $composerConfig['require']['php']
            ?? '7.4';

        $minPhpVersion = $this->extractActualPhpVersion($phpVersionConstraint);

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
