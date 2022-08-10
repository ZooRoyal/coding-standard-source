<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPCopyPasteDetector;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\AbstractTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Extension\FileExtensionTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Extension\FileExtensionTrait;
use function Safe\sprintf;

class TerminalCommand extends AbstractTerminalCommand implements
    ExclusionTerminalCommand,
    FileExtensionTerminalCommand
{
    use ExclusionTrait, FileExtensionTrait;

    private const TEMPLATE = 'php %1$s --fuzzy %3$s%2$s .';
    private const STATIC_EXCLUDES
        = [
            'custom/plugins/ZRBannerSlider/ZRBannerSlider.php',
            'custom/plugins/ZRPreventShipping/ZRPreventShipping.php',
        ];
    private Environment $environment;
    private ProcessRunner $processRunner;

    public function __construct(Environment $environment, ProcessRunner $processRunner)
    {
        $this->environment = $environment;
        $this->processRunner = $processRunner;
    }

    /**
     * {@inheritDoc}
     */
    protected function compile(): void
    {
        $vendorPath = $this->environment->getVendorDirectory()->getRealPath();

        $terminalApplication = $vendorPath . '/bin/phpcpd';

        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $terminalApplication,
            $this->buildExcludingString(),
            $this->buildExtensionsString()
        );

        $this->command = $sprintfCommand;
        $this->commandParts = explode(' ', $sprintfCommand);
    }

    /**
     * This method returns the string representation of the excluded files list.
     */
    private function buildExcludingString(): string
    {
        $excludesFilePaths = [];
        $finderResultLines = [];
        $rootPath = $this->environment->getRootDirectory()->getRealPath();

        if ($this->excludesFiles !== []) {
            $excludesFilePaths = array_map(
                static fn(EnhancedFileInfo $item) => $item->getRelativePathname() . '/',
                $this->excludesFiles
            );
        }

        $command = ['find', $rootPath, '-path', '*/custom/plugins/*', '-name', 'Installer.php', '-maxdepth', '4'];

        $finderResult = $this->processRunner->runAsProcess(...$command);

        if (!empty($finderResult)) {
            $finderResultLines = explode(PHP_EOL, trim($finderResult));
        }

        $exclusions = [...$excludesFilePaths, ...self::STATIC_EXCLUDES, ...$finderResultLines];
        $excludingString = $this->collapseExcludes($exclusions);

        return $excludingString;
    }

    private function buildExtensionsString(): string
    {
        $extensionsString = '';
        if ($this->fileExtensions !== []) {
            $extensionsString = '--suffix ' . implode(' --suffix ', $this->fileExtensions);
            $extensionsString .= ' ';
        }
        return $extensionsString;
    }

    /**
     * Collapse the excludes to a string like '--exclude asdasd --exclude qweqwe'.
     *
     * @param array<string> $finderResultLines
     */
    private function collapseExcludes(array $finderResultLines): string
    {
        return implode(
            ' ',
            array_map(
                static fn(string $item) => '--exclude ' . $item,
                $finderResultLines
            )
        );
    }
}
