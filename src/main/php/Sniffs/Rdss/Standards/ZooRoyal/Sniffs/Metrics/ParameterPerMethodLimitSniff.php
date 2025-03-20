<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ParameterPerMethodLimitSniff implements Sniff
{
    private const string ERROR_MESSAGE = 'function %s() has too many parameters: %d. Can be up to %d parameters.';

    public int $maxCount = 10;

    /** @return array<int> */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /** @param int $position */
    public function process(File $file, $position): void
    {
        $parameterCount = $this->getParameterCount($file, $position);
        $methodName = $file->getTokens()[$file->findNext(T_STRING, $position)]['content'];
        if ($parameterCount > $this->maxCount) {
            $message = sprintf(self::ERROR_MESSAGE, $methodName, $parameterCount, $this->maxCount);
            $file->addError($message, $position, 'ParameterPerMethodLimit');
        }
    }

    private function getParameterCount(File $file, int $position): int
    {
        $count = 0;
        $tokens = $file->getTokens();
        $token = $tokens[$position];
        $pointer = $token['parenthesis_closer'];
        for ($i = $position; $i < $pointer; $i++) {
            if ($tokens[$i]['code'] === T_VARIABLE) {
                $count++;
            }
        }
        return $count;
    }
}
