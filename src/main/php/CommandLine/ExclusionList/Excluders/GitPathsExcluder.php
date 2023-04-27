<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

class GitPathsExcluder implements ExcluderInterface
{
    /** @var array<string,array<EnhancedFileInfo>> */
    private array $cache = [];

    /**
     * GitPathsExcluder constructor.
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory,
        private readonly CacheKeyGenerator $cacheKeyGenerator,
        private readonly FastCachedFileSearch $fastCachedFileSearch,
    ) {
    }

    /**
     * The methods search for Git submodules and returns their paths.
     *
     * @param array<EnhancedFileInfo> $alreadyExcludedPaths
     * @param array<mixed>            $config
     *
     * @return array<EnhancedFileInfo>
     */
    public function getPathsToExclude(array $alreadyExcludedPaths, array $config = []): array
    {
        $cacheKey = $this->cacheKeyGenerator->generateCacheKey($alreadyExcludedPaths);

        if (!empty($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $rootDirectory = $this->environment->getRootDirectory();

        $foundFiles = $this->fastCachedFileSearch->listFolderFiles('.git', $rootDirectory, $alreadyExcludedPaths, 2);

         $absoluteDirectories = array_map(static fn(EnhancedFileInfo $file) => $file->getPath(), $foundFiles);
        $result = $this->enhancedFileInfoFactory->buildFromArrayOfPaths($absoluteDirectories);

        $this->cache[$cacheKey] = $result;
        return $result;
    }
}
