<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;

class TokenExcluder implements ExcluderInterface
{
    /** @var array<string,array<EnhancedFileInfo>> */
    private array $cache = [];

    /**
     * TokenExcluder constructor.
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory,
        private readonly CacheKeyGenerator $cacheKeyGenerator,
        private readonly FileSearchInterface $fileSearch,
    ) {
    }

    /**
     * This method searches for paths which contain a file by the name of $config['token']. It will not search in
     * $alreadyExcludedPaths to speed things up.
     *
     * @param array<EnhancedFileInfo> $alreadyExcludedPaths
     * @param array<mixed>            $config
     *
     * @return array<EnhancedFileInfo>
     */
    public function getPathsToExclude(array $alreadyExcludedPaths, array $config = []): array
    {
        if (!isset($config['token'])) {
            return [];
        }

        $cacheKey = $this->cacheKeyGenerator->generateCacheKey($alreadyExcludedPaths, $config);

        if (!empty($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $token = $config['token'];

        $rootDirectory = $this->environment->getRootDirectory();

        $foundFiles = $this->fileSearch->listFolderFiles($token, $rootDirectory, $alreadyExcludedPaths);

        $absoluteDirectories = array_map(static fn(EnhancedFileInfo $file) => $file->getPath(), $foundFiles);
        $result = $this->enhancedFileInfoFactory->buildFromArrayOfPaths($absoluteDirectories);

        $this->cache[$cacheKey] = $result;
        return $result;
    }
}
