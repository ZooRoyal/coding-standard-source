<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\JSESLint;

use Override;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\AbstractTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Extension\FileExtensionTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Extension\FileExtensionTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Fix\FixTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Fix\FixTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTrait;

// phpcs:ignore ZooRoyal.TypeHints.LimitUseStatement.TooManyUseStatements
class TerminalCommand extends AbstractTerminalCommand implements
    FixTerminalCommand,
    TargetTerminalCommand,
    ExclusionTerminalCommand,
    VerboseTerminalCommand,
    FileExtensionTerminalCommand
{
    use TargetTrait;
    use FixTrait;
    use ExclusionTrait;
    use FileExtensionTrait;
    use VerboseTrait;

    private const string TEMPLATE
        = 'npx %8$s--no-install eslint %6$s%7$s--no-error-on-unmatched-pattern --no-eslintrc --config %1$s %3$s'
        . '--ignore-path %2$s %4$s%5$s';

    public function __construct(private readonly Environment $environment)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function compile(): void
    {
        $this->validateTargets();
        $esLintConfigPath = $this->environment->getPackageDirectory()->getRealPath() . '/config/eslint/';

        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $esLintConfigPath . 'eslint.config.js',
            $esLintConfigPath . '.eslintignore',
            $this->buildExtensionString(),
            $this->buildExcludingString(),
            $this->buildTargetingString(),
            $this->buildVerbosityString(),
            $this->buildFixingString(),
            $this->buildPrefixString(),
        );

        $this->command = $sprintfCommand;
        $this->commandParts = explode(' ', $sprintfCommand);
    }

    /**
     * This method returns the string representation of allowed file extensions.
     */
    private function buildExtensionString(): string
    {
        $extensionString = '';
        if ($this->fileExtensions !== []) {
            $extensionString = '--ext ' . implode(' --ext ', $this->fileExtensions);
            $extensionString .= ' ';
        }
        return $extensionString;
    }

    /**
     * This method returns the string representation of the verbosity level.
     */
    private function buildVerbosityString(): string
    {
        $verbosityString = '';
        if ($this->verbosityLevel > OutputInterface::VERBOSITY_NORMAL) {
            $verbosityString = '--debug ';
        } elseif ($this->verbosityLevel < OutputInterface::VERBOSITY_NORMAL) {
            $verbosityString = '--quiet ';
        }
        return $verbosityString;
    }

    /**
     * This method returns the string representation of the excluded files list.
     */
    private function buildExcludingString(): string
    {
        $excludingString = '';
        if ($this->excludesFiles !== []) {
            $excludingString = '--ignore-pattern ';
            $excludesFilePaths = array_map(
                static fn(EnhancedFileInfo $item) => $item->getRelativePathname(),
                $this->excludesFiles,
            );
            $excludingString .= implode(' --ignore-pattern ', $excludesFilePaths);
            $excludingString .= ' ';
        }
        return $excludingString;
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

    /**
     * This method returns the string representation of the fixing mode flag.
     */
    private function buildFixingString(): string
    {
        $fixingString = '';
        if ($this->fixingMode) {
            $fixingString = '--fix ';
        }
        return $fixingString;
    }

    private function buildPrefixString(): string
    {
        $path = $this->environment->getVendorDirectory()->getRealPath() . '/..';

        return '--prefix ' . $path . ' ';
    }
}
