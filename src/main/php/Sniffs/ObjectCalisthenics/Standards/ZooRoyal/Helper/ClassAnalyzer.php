<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Exception\NonClassTypeTokenTypeException;

final class ClassAnalyzer
{
    const int ONE = 1;
    /** @var array<mixed> */
    private static array $propertyList = [];

    public static function getClassMethodCount(File $file, int $position): int
    {
        $methodCount = 0;
        $pointer = $position;

        while (($next = $file->findNext(T_FUNCTION, $pointer + self::ONE)) !== false) {
            ++$methodCount;

            $pointer = $next;
        }

        return $methodCount;
    }

    public static function getClassPropertiesCount(File $file, int $position): int
    {
        return count(self::getClassProperties($file, $position));
    }

    /**
     * @return array<mixed>
     */
    public static function getClassProperties(File $file, int $position): array
    {
        $tokens = $file->getTokens();
        $token = $tokens[$position];
        $pointer = $token['scope_opener'];

        self::$propertyList = [];

        while (($pointer = $file->findNext(T_VARIABLE, ($pointer + self::ONE), $token['scope_closer'])) !== false) {
            self::extractPropertyIfFound($file, $pointer);
        }

        return self::$propertyList;
    }

    private static function extractPropertyIfFound(File $file, int $position): void
    {
        if (PropertyHelper::isProperty($file, $position)) {
            self::$propertyList[] = $position;
        }
    }


}
