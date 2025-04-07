<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\FileSearch;

use Override;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;

use function Safe\scandir;

class FastCachedFileSearch implements FileSearchInterface
{
    /** @var array<string,array<string>> */
    private array $fileSystemCache = [];

    public function __construct(private readonly EnhancedFileInfoFactory $enhancedFileInfoFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function listFolderFiles(
        string $fileName,
        EnhancedFileInfo $path,
        array $exclusions = [],
        int $minDepth = 0,
        ?int $maxDepth = null,
    ): array {
        $dir = $path->getPathname();
        $exclusionPaths = array_map(
            static fn(EnhancedFileInfo $exclusion) => $exclusion->getPathname(),
            $exclusions,
        );

        $resultingPathnames = $this->doTheSearch($fileName, $dir, $exclusionPaths, $minDepth, $maxDepth, 0);

        return $this->enhancedFileInfoFactory->buildFromArrayOfPaths($resultingPathnames);
    }

    /**
     * This method is the entrypoint of the search algorithm. This is the receiving end of a recursive call. It is
     * singled out to have full control over the recursion.
     *
     * @param array<string> $exclusions
     *
     * @return array<string>
     */
    private function doTheSearch(
        string $name,
        string $directory,
        array &$exclusions,
        int $minDepth,
        ?int $maxDepth,
        int $depthNow,
    ): array {
        $result = [];

        if ($maxDepth === $depthNow) {
            return $result;
        }

        // If name is not a directory, return empty array
        if (
            (isset($this->fileSystemCache[$directory]) && $this->fileSystemCache[$directory] === [])
            || !is_dir($directory)
        ) {
            $this->fileSystemCache[$directory] = [];
            return $result;
        }

        // Writing the cache for the current directory
        $this->writeDirectoryToCache($directory);

        // If directory is empty, return empty array
        if ($this->fileSystemCache[$directory] === []) {
            return $result;
        }

        $result = $this->searchForFileInCache(
            $name,
            $directory,
            $exclusions,
            $minDepth,
            $maxDepth,
            $depthNow,
        );

        return $result;
    }

    /**
     * Writes information about the files in a directory to the cache.
     */
    private function writeDirectoryToCache(string $dir): void
    {
        if (!isset($this->fileSystemCache[$dir])) {
            $filenamesInDirectory = scandir($dir);

            unset(
                $filenamesInDirectory[array_search('.', $filenamesInDirectory, true)],
                $filenamesInDirectory[array_search('..', $filenamesInDirectory, true)],
            );

            $this->fileSystemCache[$dir] = $filenamesInDirectory;
        }
    }

    /**
     * This method is only searching the cache for files of a given name in a directory and its subdirectories. This
     * is the core of the search algorithm and the starting point for recursion.
     *
     * @param array<string> $exclusions
     *
     * @return array<string>
     */
    private function searchForFileInCache(
        string $name,
        string $directory,
        array &$exclusions,
        int $minDepth,
        ?int $maxDepth,
        int $depthNow,
    ): array {
        $result = [];
        foreach ($this->fileSystemCache[$directory] as $filename) {
            $filePathname = $directory . '/' . $filename;

            // If file has the correct name and is deep enough, add it to the result
            if ($minDepth <= $depthNow && $filename === $name) {
                $result[] = $filePathname;
            }

            // If directory is excluded, jump to next iteration
            if ($exclusions !== []) {
                $key = array_search($filePathname, $exclusions, true);
                if ($key !== false) {
                    unset($exclusions[$key]);
                    continue;
                }
            }

            // Try searching for name in $filePathname. Starting point for recursion.
            $subFolderResults = $this->doTheSearch(
                $name,
                $filePathname,
                $exclusions,
                $minDepth,
                $maxDepth,
                $depthNow + 1,
            );

            $result = [...$result, ...$subFolderResults];
        }
        return $result;
    }
}
