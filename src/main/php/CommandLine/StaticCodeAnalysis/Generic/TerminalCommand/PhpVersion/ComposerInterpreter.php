<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;

use function Safe\file_get_contents;

class ComposerInterpreter
{
    private const int SEARCH_DEPTH_MIN = 1;
    private const int SEARCH_DEPTH_MAX = 4;

    private ?string $cachedMinimalViablePhpVersion = null;
    private string $cachedMinimalRootPackagePhpVersion;

    public function __construct(
        private readonly Environment $environment,
        private readonly ConstraintToVersionConverter $constraintToVersionConverter,
        private readonly FileSearchInterface $fileSearch,
    ) {
    }

    /**
     * Reads the php version constraint from the root composer.json. Does not look at PHP version constraints in
     * dependencies.
     */
    private function readConstraintFromRootComposerFile(): string
    {
        $rootDirectory = $this->environment->getRootDirectory();
        $path = $rootDirectory->getRealPath();
        $composerConfig = json_decode(
            file_get_contents($path . '/composer.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $phpVersionConstraint = $composerConfig['config']['platform']['php']
            ?? $composerConfig['require']['php']
            ?? '*';

        return $phpVersionConstraint;
    }

    /**
     * Get local php-version-constraints from root composer.json.
     */
    public function getMinimalRootPackagePhpVersion(): string
    {
        if (isset($this->cachedMinimalRootPackagePhpVersion)) {
            return $this->cachedMinimalRootPackagePhpVersion;
        }

        $phpVersionConstraint = $this->readConstraintFromRootComposerFile();
        $this->cachedMinimalRootPackagePhpVersion = $this->constraintToVersionConverter
            ->extractActualPhpVersion($phpVersionConstraint);

        return $this->cachedMinimalRootPackagePhpVersion;
    }

    /**
     * Get the minimal viable PHP version from all composer files in the project. This is the lowest common PHP version
     * and our best guess for the minimal PHP version required to run the project.
     */
    public function getMinimalViablePhpVersion(): string
    {
        if ($this->cachedMinimalViablePhpVersion === null) {
            $composerFiles = $this->gatherComposerFiles();
            $this->cachedMinimalViablePhpVersion = $this->searchMinimalViablePhpVersion($composerFiles);
        }

        return $this->cachedMinimalViablePhpVersion;
    }

    /**
     * Finds all composer files in the project.
     *
     * @return array<EnhancedFileInfo>
     */
    private function gatherComposerFiles(): array
    {
        $rootDirectory = $this->environment->getRootDirectory();

        $foundComposerFiles = $this->fileSearch->listFolderFiles(
            fileName: 'composer.json',
            path: $rootDirectory,
            minDepth: self::SEARCH_DEPTH_MIN,
            maxDepth: self::SEARCH_DEPTH_MAX,
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
        $minimalViablePhpVersion = $this->getMinimalRootPackagePhpVersion();

        foreach ($composerFiles as $composerFile) {
            $contents = file_get_contents($composerFile->getRealPath());
            $composerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $phpVersionConstraint = $composerConfig['require']['php'] ?? '*';
            $minPhpVersionPackage = $this->constraintToVersionConverter->extractActualPhpVersion($phpVersionConstraint);
            $minimalViablePhpVersion = version_compare($minimalViablePhpVersion, $minPhpVersionPackage, '<')
                ? $minPhpVersionPackage
                : $minimalViablePhpVersion;
        }
        return $minimalViablePhpVersion;
    }
}
