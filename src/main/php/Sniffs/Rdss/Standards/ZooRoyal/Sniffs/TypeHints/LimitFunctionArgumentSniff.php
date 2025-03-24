<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;

/**
 * Replaces the native PHPMD ExcessiveParameterList check.
 */
class LimitFunctionArgumentSniff implements Sniff
{
    public int $maximumArguments = 10;

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        return [T_FUNCTION];
    }

    /**
     * {@inheritDoc}
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $parameterNames = FunctionHelper::getParametersNames($phpcsFile, $stackPtr);

        if (count($parameterNames) > $this->maximumArguments) {
            $error = 'Method has too many parameters. Maximum allowed is %s, but found %s.';
            $data = [$this->maximumArguments, count($parameterNames)];
            $phpcsFile->addError($error, $stackPtr, 'TooManyArguments', $data);
        }
    }
}
