<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ObjectCouplingLimitSniff implements Sniff
{
    private const string ERROR_MESSAGE
        = 'The class %s has a coupling between objects value of %s. Consider to reduce the number of dependencies under %s.';

    public int $maxCount = 15;

    /**
     * Description is in the inherited doc.
     *
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint We must
     *                stay compatible with the interface even if we don't like it.
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    /**
     * Description is in the inherited doc.
     *
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint We must
     *                stay compatible with the interface even if we don't like it.
     */
    public function process(File $file, $position): void
    {
        $usesCount = 0;
        $tokens = $file->getTokens();
        $pointer = 0;
        while (($next = $file->findNext(T_USE, $pointer + 1)) !== false) {
            $usesCount++;
            $pointer = $next;
        }
        if ($usesCount > $this->maxCount) {
            $className = $tokens[$file->findNext(T_STRING, $position)]['content'];
            $message = sprintf(self::ERROR_MESSAGE, $className, $usesCount, $this->maxCount + 1);
            $file->addError($message, $position, 'ObjectCouplingLimit');
        }
    }
}
