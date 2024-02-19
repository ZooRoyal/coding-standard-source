<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use JsonException;
use Safe\Exceptions\FilesystemException;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

use function Safe\file_get_contents;

class ComposerInterpreter
{
    public function __construct(
        private readonly Environment $environment,
        private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory,
        private readonly ConstraintToVersionConverter $constraintToVersionConverter,
    ) {
    }

    /**
     * Get local php-version-constraints from root composer.json.
     *
     * @throws FilesystemException
     * @throws JsonException
     */
    public function getLocalPhpVersionConstraint(): string
    {
        $rootDirectory = $this->environment->getRootDirectory();
        $path = $rootDirectory->getRealPath();
        $composerFile = $this->enhancedFileInfoFactory->buildFromPath($path . '/composer.json');
        $composerConfig = json_decode(
            file_get_contents($composerFile->getRealPath()),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $phpVersionConstraint = $composerConfig['config']['platform']['php']
            ?? $composerConfig['require']['php']
            ?? '*';

        $phpVersionConstraintExtracted = $this->constraintToVersionConverter
            ->extractActualPhpVersion($phpVersionConstraint);

        return $phpVersionConstraintExtracted;
    }
}
