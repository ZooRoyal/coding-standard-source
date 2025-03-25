<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

class LimitUseStatementSniff implements Sniff
{
    public int $maximumUseStatements = 15;

    /**
     * Description is in the inherited doc.
     *
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint We must
     *                stay compatible with the interface even if we don't like it.
     */
    public function register()
    {
        return [T_CLASS];
    }

    /**
     * Description is in the inherited doc.
     *
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint We must
     *                stay compatible with the interface even if we don't like it.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $usePointers = UseStatementHelper::getFileUseStatements($phpcsFile);

        if (!isset($usePointers[11])) {
            return;
        }

        if (count($usePointers[11]) > $this->maximumUseStatements) {
            $error = 'Too many use statements. Maximum allowed is %s, but found %s.';
            $data = [$this->maximumUseStatements, count($usePointers[11])];
            $phpcsFile->addError($error, $stackPtr, 'TooManyUseStatements', $data);
        }
    }
}
