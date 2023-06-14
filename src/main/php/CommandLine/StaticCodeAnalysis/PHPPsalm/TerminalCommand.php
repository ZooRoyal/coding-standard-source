<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

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
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTrait;

class TerminalCommand extends AbstractTerminalCommand implements
    FixTerminalCommand,
    TargetTerminalCommand,
    ExclusionTerminalCommand,
    VerboseTerminalCommand,
    MultiprocessTerminalCommand,
    VersionDependentTerminalCommand
{
    use TargetTrait;
    use FixTrait;
    use ExclusionTrait;
    use VerboseTrait;
    use MultiprocessTrait;
    use VersionDependentTrait;

    private const TEMPLATE = 'php %1$s --config=%2$s%3$s%4$s%5$s%6$s';

    public function __construct(
        private readonly Environment $environment,
        private readonly PhpVersionConverter $phpVersionConverter,
        private readonly PHPPsalmProjectFilesGenerator $PHPPsalmProjectFilesGenerator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function compile(): void
    {
        $this->validateTargets();
        $this->PHPPsalmProjectFilesGenerator->writeProjectFilesFile($this->output, $this->excludesFiles, $this->targetedFiles);
        $vendorPath = $this->environment->getVendorDirectory()->getRealPath();
        $phpPsalmConfigPath = $this->environment->getPackageDirectory()->getRealPath()
            . '/config/psalm/psalm.xml';

        $terminalApplication =  $vendorPath . '/bin/psalm';

        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $terminalApplication,
            $phpPsalmConfigPath,
            $this->buildVerbosityString(),
            $this->maxConcurrentProcesses(),
            $this->buildPhpVersionString(),
            $this->buildFixModeString(),
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
            $verbosityString = ' --debug --show-info=true';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $verbosityString = ' --debug-by-line --show-info=true';
        } elseif ($this->verbosityLevel === OutputInterface::VERBOSITY_DEBUG) {
            $verbosityString = ' --debug-emitted-issues --show-info=true';
        }
        return $verbosityString;
    }

    /**
     * This method returns the string representation of the php versqwion.
     */
    private function buildPhpVersionString(): string
    {
        $template = ' --php-version=%s';
        $versionsStrings = explode(".", $this->phpVersion);
        unset($versionsStrings[2]);
        $phpVersion = implode(".", $versionsStrings);
        $result = sprintf($template, $phpVersion);

        return $result;
    }


    /**
     * This method returns string representation of psalm-process-threading
     */
    private function maxConcurrentProcesses() : string
    {
        $template = ' --threads=%d';
        $result = sprintf($template, $this->maxConcurrentProcesses);

        return $result;
    }

    /**
     * This method returns string representation of the fixing-mode
     */
    private function buildFixModeString() : string
    {
        $result =  $this->fixingMode
            ? ' --alter --issues=MissingReturnType,MissingClosureReturnType,InvalidReturnType,InvalidNullableReturnType,InvalidFalsableReturnType,MissingParamType,MissingPropertyType,MismatchingDocblockParamType,MismatchingDocblockReturnType,LessSpecificReturnType,PossiblyUndefinedVariable,PossiblyUndefinedGlobalVariable,UnusedMethod,PossiblyUnusedMethod,UnusedProperty,PossiblyUnusedProperty,UnusedVariable,UnnecessaryVarAnnotation,ParamNameMismatch --allow-backwards-incompatible-changes=false'
            : '';

        return $result;
    }
}
