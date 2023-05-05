<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\FileSearch;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;

interface FileSearchInterface
{
    /**
     * This method searches for files of a given name in a directory and its subdirectories. It returns an array of
     * EnhancedFileInfo matching the search criteria.
     *
     * @param EnhancedFileInfo        $path The directory to search in
     * @param array<EnhancedFileInfo> $exclusions
     *
     * @return array<EnhancedFileInfo>
     */
    public function listFolderFiles(
        string $fileName,
        EnhancedFileInfo $path,
        array $exclusions = [],
        int $minDepth = 0,
        ?int $maxDepth = null,
    ): array;
}
