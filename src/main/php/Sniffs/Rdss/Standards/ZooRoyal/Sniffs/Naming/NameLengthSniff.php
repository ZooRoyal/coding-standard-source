<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;

class NameLengthSniff implements Sniff
{
    public int $minimumLength = 3;
    public int $maximumLength = 70;

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
        return [T_VARIABLE, T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT, T_PROPERTY];
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
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr]['content'];

        $name = match ($token) {
            'function' => FunctionHelper::getName($phpcsFile, $stackPtr),
            'class', 'trait', 'interface' => ClassHelper::getName($phpcsFile, $stackPtr),
            default => $this->getName($tokens[$stackPtr]['content']),
        };

        if (strlen($name) > $this->maximumLength) {
            $error = 'Name "%s" is greater than %s characters long';
            $data = [$name, $this->maximumLength];
            $phpcsFile->addError($error, $stackPtr, 'NameTooLong', $data);
        }

        if (strlen($name) < $this->minimumLength) {
            $error = 'Name "%s" is less than %s characters long';
            $data = [$name, $this->minimumLength];
            $phpcsFile->addError($error, $stackPtr, 'NameTooShort', $data);
        }
    }


    private function getName(string $content): string
    {
        // Add exclusion for variable $i so you cant use it in for loops.
        if ($content === '$i') {
            return str_pad($content, $this->maximumLength, 'i');
        }

        return ltrim($content, '$');
    }
}
