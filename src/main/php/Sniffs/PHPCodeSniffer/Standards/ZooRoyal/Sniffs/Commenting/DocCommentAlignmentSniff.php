<?php
// phpcs:disable
/**
 * Tests that the stars in a doc comment align correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

// phpcs:enable
namespace Zooroyal\CodingStandard\Sniffs\PHPCodeSniffer\Standards\ZooRoyal\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use const T_DOC_COMMENT_CLOSE_TAG;
use const T_DOC_COMMENT_OPEN_TAG;
use const T_DOC_COMMENT_STAR;
use const T_DOC_COMMENT_TAG;
use const T_DOC_COMMENT_WHITESPACE;
use const T_EQUAL;
use const T_OBJECT;
use const T_PROPERTY;
use const T_PROTOTYPE;

/**
 * Class DocCommentAlignmentSniff
 * This class is a copy of PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\DocCommentAlignmentSniff.
 *
 * This version ignores OpenAPI (OA) comments that may be deeply nested.
 *
 */
class DocCommentAlignmentSniff implements Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_DOC_COMMENT_OPEN_TAG];
    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // We are only interested in function/class/interface doc block comments.
        $ignore = Tokens::$emptyTokens;
        if ($phpcsFile->tokenizerType === 'JS') {
            $ignore[] = T_EQUAL;
            $ignore[] = T_STRING;
            $ignore[] = T_OBJECT_OPERATOR;
        }

        $nextToken = $phpcsFile->findNext($ignore, ($stackPtr + 1), null, true);
        $ignore    = [
            T_CLASS     => true,
            T_INTERFACE => true,
            T_FUNCTION  => true,
            T_PUBLIC    => true,
            T_PRIVATE   => true,
            T_PROTECTED => true,
            T_STATIC    => true,
            T_ABSTRACT  => true,
            T_PROPERTY  => true,
            T_OBJECT    => true,
            T_PROTOTYPE => true,
            T_VAR       => true,
        ];

        if ($nextToken === false || isset($ignore[$tokens[$nextToken]['code']]) === false) {
            // Could be a file comment.
            $prevToken = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
            if ($tokens[$prevToken]['code'] !== T_OPEN_TAG) {
                return;
            }
        }

        // There must be one space after each star (unless it is an empty comment line)
        // and all the stars must be aligned correctly.
        $requiredColumn = ($tokens[$stackPtr]['column'] + 1);
        $endComment     = $tokens[$stackPtr]['comment_closer'];
        for ($i = ($stackPtr + 1); $i <= $endComment; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STAR
                && $tokens[$i]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            ) {
                continue;
            }

            if ($tokens[$i]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
                if (trim($tokens[$i]['content']) === '') {
                    // Don't process an unfinished docblock close tag during live coding.
                    continue;
                }

                // Can't process the close tag if it is not the first thing on the line.
                $prev = $phpcsFile->findPrevious(T_DOC_COMMENT_WHITESPACE, ($i - 1), $stackPtr, true);
                if ($tokens[$prev]['line'] === $tokens[$i]['line']) {
                    continue;
                }
            }

            if ($tokens[$i]['column'] !== $requiredColumn) {
                $error = 'Expected %s space(s) before asterisk; %s found';
                $data  = [
                    ($requiredColumn - 1),
                    ($tokens[$i]['column'] - 1),
                ];
                $fix   = $phpcsFile->addFixableError($error, $i, 'SpaceBeforeStar', $data);
                if ($fix === true) {
                    $padding = str_repeat(' ', ($requiredColumn - 1));
                    if ($tokens[$i]['column'] === 1) {
                        $phpcsFile->fixer->addContentBefore($i, $padding);
                    } else {
                        $phpcsFile->fixer->replaceToken(($i - 1), $padding);
                    }
                }
            }

            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STAR) {
                continue;
            }

            if ($tokens[($i + 2)]['line'] !== $tokens[$i]['line']) {
                // Line is empty.
                continue;
            }

            if ($tokens[($i + 1)]['code'] !== T_DOC_COMMENT_WHITESPACE) {
                $error = 'Expected 1 space after asterisk; 0 found';
                $fix   = $phpcsFile->addFixableError($error, $i, 'NoSpaceAfterStar');
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($i, ' ');
                }
            } elseif ($tokens[($i + 2)]['code'] === T_DOC_COMMENT_TAG
                && $tokens[($i + 1)]['content'] !== ' '
                && strtolower(substr($tokens[($i + 1)]['content'], 0, 3)) !== '@oa'
                && strtolower(substr($tokens[($i + 2)]['content'], 0, 3)) !== '@oa'
            ) {
                $error = 'Expected 1 space after asterisk; %s found';
                $data  = [$tokens[($i + 1)]['length']];
                $fix   = $phpcsFile->addFixableError($error, $i, 'SpaceAfterStar', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($i + 1), ' ');
                }
            }
        }//end for
    }//end process()
}//end class
