<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ObjectCouplingLimitSniff implements Sniff
{
    /**
     * @var string
     */
    private const string ERROR_MESSAGE = 'The class %s has a coupling between objects value of %s. Consider to reduce the number of dependencies under %s.';

    public int $maxCount = 15;

    /**
     * @return array<int>
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    public function process(File $file, $position): void
    {
        $usesCount = 0;
        $pointer = 0;
        while (($next = $file->findNext(T_USE, $pointer + 1)) !== false) {
            $usesCount++;
            $pointer = $next;
        }
        if ($usesCount > $this->maxCount) {
            $className = $file->getTokens()[$file->findNext(T_STRING, $position)]['content'];
            $message = sprintf(self::ERROR_MESSAGE, $className, $usesCount, $this->maxCount + 1);
            $file->addError($message, $position, 'ParameterPerMethodLimit');
        }
    }
}
