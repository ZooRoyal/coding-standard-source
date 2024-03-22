<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPCodeSniffer;

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
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Multiprocess\MultiprocessTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Multiprocess\MultiprocessTrait;
// phpcs:ignore -- I did not find a way to either break this line or to make it shorter.
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\MinimalVersionDependantTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\MinimalVersionDependentTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTrait;

class TerminalCommand extends AbstractTerminalCommand implements
    FixTerminalCommand,
    TargetTerminalCommand,
    ExclusionTerminalCommand,
    FileExtensionTerminalCommand,
    VerboseTerminalCommand,
    MultiprocessTerminalCommand,
    MinimalVersionDependantTerminalCommand
{
    use TargetTrait;
    use FixTrait;
    use ExclusionTrait;
    use FileExtensionTrait;
    use VerboseTrait;
    use MultiprocessTrait;
    use MinimalVersionDependentTrait;

    private const TEMPLATE = 'php %1$s %5$s%6$s--parallel=%7$d -p --standard=%2$s%3$s%8$s%4$s';

    public function __construct(
        private readonly Environment $environment,
        private readonly PhpVersionConverter $phpVersionConverter,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function compile(): void
    {
        $this->validateTargets();

        $vendorPath = $this->environment->getVendorDirectory()->getRealPath();
        $phpCodeSnifferConfig = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/phpcs/ZooRoyal/ruleset.xml';

        $terminalApplication = $this->fixingMode
            ? $vendorPath . '/bin/phpcbf'
            : $vendorPath . '/bin/phpcs -s';

        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $terminalApplication,
            $phpCodeSnifferConfig,
            $this->buildExcludingString(),
            $this->buildTargetingString(),
            $this->buildVerbosityString(),
            $this->buildExtensionString(),
            $this->maxConcurrentProcesses,
            $this->buildPhpVersionString(),
        );

        $this->command = $sprintfCommand;
        $this->commandParts = explode(' ', $sprintfCommand);
    }

    private function buildExtensionString(): string
    {
        $extensionString = '';
        if ($this->fileExtensions !== []) {
            $extensionString = '--extensions=' . implode(',', $this->fileExtensions);
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
        if ($this->verbosityLevel === OutputInterface::VERBOSITY_VERBOSE) {
            $verbosityString = '-v ';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $verbosityString = '-vv ';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_DEBUG) {
            $verbosityString = '-vvv ';
        } elseif ($this->verbosityLevel < OutputInterface::VERBOSITY_NORMAL) {
            $verbosityString = '-q ';
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
            $excludingString = ' --ignore=';
            $excludesFilePaths = array_map(
                static fn(EnhancedFileInfo $item) => $item->getRealPath(),
                $this->excludesFiles,
            );
            $excludingString .= implode(',', $excludesFilePaths);
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
            $targetingString = ' ' . implode(' ', $targetedFilePaths);
        } else {
            $targetingString = ' ' . $this->environment->getRootDirectory()->getRelativePathname();
        }
        return $targetingString;
    }

    /**
     * This method returns the string representation of the php version.
     */
    private function buildPhpVersionString(): string
    {
        $template = ' --runtime-set php_version %1$d --runtime-set testVersion %2$s';
        $phpVersionPhpStyle = $this->phpVersionConverter->convertSemVerToPhpString($this->minimalPhpVersion);

        $phpVersionLevels = explode('.', $this->minimalPhpVersion);
        $phpVersionWithoutPatchLevel = implode('.', [$phpVersionLevels[0], $phpVersionLevels[1]]);

        $result = sprintf($template, $phpVersionPhpStyle, $phpVersionWithoutPatchLevel . '-');

        return $result;
    }
}
