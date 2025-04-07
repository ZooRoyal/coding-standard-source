<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders;

use Override;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;

class GitIgnoresExcluder implements ExcluderInterface
{
    private const string GIT_LS_FILES_COMMAND = 'git ls-files -io --exclude-standard --directory';

    /** @var array<EnhancedFileInfo> */
    private array $cache = [];

    /**
     * GitIgnoresExcluder constructor.
     */
    public function __construct(
        private readonly ProcessRunner $processRunner,
        private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory,
    ) {
    }

    /**
     * This Method ask Git which folders should be ignored and returns them if they are found.
     *
     * @param array<EnhancedFileInfo> $alreadyExcludedPaths
     * @param array<mixed>            $config
     *
     * @return array<EnhancedFileInfo>
     */
    #[Override]
    public function getPathsToExclude(array $alreadyExcludedPaths, array $config = []): array
    {
        if ($this->cache !== []) {
            return $this->cache;
        }

        $rawIgnoredFoldersAndFilesString = $this->processRunner->runAsProcess(self::GIT_LS_FILES_COMMAND);

        if ($rawIgnoredFoldersAndFilesString === '' || $rawIgnoredFoldersAndFilesString === '0') {
            return [];
        }
        $rawIgnoredFoldersAndFiles = explode("\n", $rawIgnoredFoldersAndFilesString);
        $rawIgnoredFolders = $this->filterForFolders($rawIgnoredFoldersAndFiles);
        $ignoredFolders = $this->enhancedFileInfoFactory->buildFromArrayOfPaths(
            $rawIgnoredFolders,
        );

        $this->cache = $ignoredFolders;
        return $ignoredFolders;
    }

    /**
     * Filters out everything which is not a Folder.
     *
     * @param array<string> $rawIgnoredFoldersAndFiles
     *
     * @return array<string>
     */
    private function filterForFolders(array $rawIgnoredFoldersAndFiles): array
    {
        $ignoredFolders = [];
        foreach ($rawIgnoredFoldersAndFiles as $ignoredFoldersAndFile) {
            if (mb_substr($ignoredFoldersAndFile, -1) === '/') {
                $ignoredFolders[] = $ignoredFoldersAndFile;
            }
        }
        return $ignoredFolders;
    }
}
