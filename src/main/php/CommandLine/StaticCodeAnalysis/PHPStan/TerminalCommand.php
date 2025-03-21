<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan;

use Override;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\AbstractTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTrait;

/** @phpcsSuppress ZooRoyal.Metrics.ObjectCouplingLimit */
class TerminalCommand extends AbstractTerminalCommand implements
    TargetTerminalCommand,
    VerboseTerminalCommand,
    VersionDependentTerminalCommand
{
    use TargetTrait;
    use VerboseTrait;
    use VersionDependentTrait;

    private const string TEMPLATE = 'php %1$s analyse %4$s--no-progress --error-format=github -c %2$s %3$s';

    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function compile(): void
    {
        $this->validateTargets();

        $vendorPath = $this->environment->getVendorDirectory()->getRealPath();

        $terminalApplication = $vendorPath . '/bin/phpstan';
        $configPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/phpstan/phpstan.neon';

        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $terminalApplication,
            $configPath,
            $this->buildTargetingString(),
            $this->buildVerbosityString(),
        );

        $this->command = $sprintfCommand;
        $this->commandParts = explode(' ', $sprintfCommand);
    }

    /**
     * This method returns the string representation of the verbosity level.
     */
    private function buildVerbosityString(): string
    {
        $verbosityString = '';
        if ($this->verbosityLevel === OutputInterface::VERBOSITY_VERBOSE) {
            $verbosityString = '-v ';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $verbosityString = '-vv ';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_DEBUG) {
            $verbosityString = '-vvv --debug ';
        } elseif ($this->verbosityLevel < OutputInterface::VERBOSITY_NORMAL) {
            $verbosityString = '-q ';
        }
        return $verbosityString;
    }

    /**
     * This method returns the string representation of the targeted files list.
     */
    private function buildTargetingString(): string
    {
        if ($this->targetedFiles !== null) {
            $targetedFilePaths = array_map(
                static fn(EnhancedFileInfo $item) => $item->getRelativePathname(),
                $this->targetedFiles,
            );
            $targetingString = implode(' ', $targetedFilePaths);
        } else {
            $targetingString = $this->environment->getRootDirectory()->getRelativePathname();
        }
        return $targetingString;
    }
}
