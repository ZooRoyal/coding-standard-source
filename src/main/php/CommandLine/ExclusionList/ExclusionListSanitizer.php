<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\ExclusionList;

use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;

class ExclusionListSanitizer
{
    /**
     * This method deletes entries from exclusionList which would have no effect.
     *
     * @param array<EnhancedFileInfo> $rawExcludePaths
     *
     * @example
     *         Input: ['./a', './a/b', './a/b/c', '/ab']
     *         Output: ['./a', '/ab']
     *         Explanation: As the second and the third directories are children of the first it would make
     *         no sense to exclude them "again". As the parent is excluded they are automatically
     *         excluded too.
     *
     * @return array<EnhancedFileInfo>
     */
    public function sanitizeExclusionList(array $rawExcludePaths): array
    {
        $filteredArray = $rawExcludePaths;
        $count = count($filteredArray);
        for ($i = 0; $count > $i; $i++) {
            if (!isset($filteredArray[$i])) {
                continue;
            }
            $item = $filteredArray[$i];
            $filteredArray = array_filter(
                $filteredArray,
                static function ($value, $key) use ($item, $i): bool {
                    if ($key === $i) {
                        return true;
                    }
                    return !$value->isSubdirectoryOf($item) && $value->getPathname() !== $item->getPathname();
                },
                ARRAY_FILTER_USE_BOTH,
            );
        }
        $filteredArray = array_values($filteredArray);

        return $filteredArray;
    }
}
