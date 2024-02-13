<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

use function Safe\file_get_contents;

class VersionDecorator extends TerminalCommandDecorator
{
    private ?string $cachedMinPhpVersion = null;

    public function __construct(
        private readonly Environment $environment,
        private readonly FileSearchInterface $fileSearchInterface,
        private readonly ConstraintToVersionConverter $constraintToVersionConverter,
        private readonly ComposerInterpreter $composerInterpreter,
    ) {
    }

    public function decorate(DecorateEvent $event): void
    {
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof VersionDependentTerminalCommand) {
            return;
        }

        if ($this->cachedMinPhpVersion === null) {
            $composerFiles = $this->gatherComposerFiles();
            $this->cachedMinPhpVersion = $this->searchMinimalViablePhpVersion($composerFiles);
        }

        $terminalCommand->setPhpVersion($this->cachedMinPhpVersion);

        $event->getOutput()->writeln(
            '<info>Targeted PHP version is ' . $this->cachedMinPhpVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
    }

    /**
     * Finds all composer files in the project.
     *
     * @return array<EnhancedFileInfo>
     */
    private function gatherComposerFiles(): array
    {
        $rootDirectory = $this->environment->getRootDirectory();

        $foundComposerFiles = $this->fileSearchInterface->listFolderFiles(
            fileName: 'composer.json',
            path: $rootDirectory,
            minDepth: 1,
            maxDepth: 4,
        );

        return $foundComposerFiles;
    }

    /**
     * Combines Constraints of given composer files and returns the lowest possible php version.
     *
     * @param array<EnhancedFileInfo> $composerFiles
     */
    private function searchMinimalViablePhpVersion(array $composerFiles): string
    {
        $minPhpVersion = $this->composerInterpreter->getLocalPhpVersionConstraint();

        foreach ($composerFiles as $composerFile) {
            $contents = file_get_contents($composerFile->getRealPath());
            $composerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $phpVersionConstraint = $composerConfig['require']['php'] ?? '*';

            $minPhpVersionPackage = $this->constraintToVersionConverter->extractActualPhpVersion($phpVersionConstraint);
            $minPhpVersion = version_compare($minPhpVersion, $minPhpVersionPackage, '<')
                ? $minPhpVersionPackage
                : $minPhpVersion;
        }
        return $minPhpVersion;
    }
}
