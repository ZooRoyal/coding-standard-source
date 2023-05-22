<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use Safe\Exceptions\DirException;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;

use function Safe\file_get_contents;
use function Safe\scandir;

class CheckSafeFunctionUsageSniff implements Sniff
{
    /** @var array<string> */
    private array $functionNames;

    public function __construct()
    {
        $container = ContainerFactory::getContainerInstance();
        $environment = $container->get(Environment::class);
        $path = $environment->getRootDirectory()->getRealPath() . '/vendor/thecodingmachine/safe/generated/';
        try {
            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            $filesUnfiltered = @scandir($path);
        } catch (DirException $exception) {
            return;
        }

        $files = array_diff($filesUnfiltered, ['.', '..', 'Exceptions']);

        $parser = $container->get(Parser::class);

        $functionNames = [];

        foreach ($files as $file) {
            $ast = $parser->parse(file_get_contents($path . $file));
            $nodeFinder = $container->get(NodeFinder::class);

            $functions = $nodeFinder->find($ast, static fn(Node $node) => $node instanceof Node\Stmt\Function_);
            $functionNamesLocal = array_map(static fn(Node\Stmt\Function_ $node) => (string) $node->name, $functions);

            $functionNames = [...$functionNames, ...$functionNamesLocal];
        }

        $this->functionNames = $functionNames;
    }

    /**
     * Description is in the inherited doc.
     *
     * @{inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification We must
     *                stay compatible with the interface even if we don't like it.
     */
    public function register(): array
    {
        return [
            T_STRING,
        ];
    }

    /**
     * Description is in the inherited doc.
     *
     * @{inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint We must
     *                stay compatible with the interface even if we don't like it.
     *
     * @throws AssertionException
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!isset($this->functionNames)) {
            throw new AssertionException(
                'No function names found! Did you forget to install thecodingmachine/Safe?',
                1684240278
            );
        }

        $tokens = $phpcsFile->getTokens();
        $functionName = strtolower(ltrim($tokens[$stackPtr]['content'], '\\'));

        try {
            $this->assertNextTokenParenthesisOpener($phpcsFile, $stackPtr);
            $this->assertGlobalFunctionCall($phpcsFile, $stackPtr);
            $this->assertFunctionProvidedBySafe($tokens[$stackPtr]['content']);
            $this->assertFunctionUnused($phpcsFile, $functionName);
        } catch (AssertionException $exception) {
            // If this is the case we found no Safe function. Continue...
            return;
        }

        $this->addErrorToPhpcsFile($phpcsFile, $stackPtr, $functionName);
    }

    private function assertNextTokenParenthesisOpener(File $phpcsFile, int $stackPtr): void
    {
        $parenthesisOpenerPointer = TokenHelper::findNextEffective($phpcsFile, $stackPtr + 1);
        if ($phpcsFile->getTokens()[$parenthesisOpenerPointer]['code'] !== T_OPEN_PARENTHESIS) {
            throw new AssertionException('No parenthesis opener found!', 1684230169);
        }
    }

    private function assertFunctionProvidedBySafe(string $functionName): void
    {
        if (!in_array($functionName, $this->functionNames, true)) {
            throw new AssertionException('Function ' . $functionName . ' not found in Safe!', 1684230170);
        }
    }

    private function assertGlobalFunctionCall(File $phpcsFile, int $stackPtr): void
    {
        $previousPointer = TokenHelper::findPreviousEffective($phpcsFile, $stackPtr - 1);
        if (
            in_array(
                $phpcsFile->getTokens()[$previousPointer]['code'],
                [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION],
                true
            )
        ) {
            throw new AssertionException('Token is not a global function call!', 1684230171);
        }
    }

    private function assertFunctionUnused(File $phpcsFile, string $functionName): void
    {
        $usesSearchResult = UseStatementHelper::getFileUseStatements($phpcsFile);

        $functionUses = [];
        if (!empty($usesSearchResult)) {
            $functionUses = array_filter(
                reset($usesSearchResult),
                static fn(UseStatement $useStatement) => $useStatement->getType() === 'function'
            );
        }

        //Search $functionUses for $functionName
        $useStatementOfFunctionName = array_filter(
            $functionUses,
            static fn(UseStatement $useStatement) => $useStatement->getNameAsReferencedInFile() === $functionName
        );
        if (count($useStatementOfFunctionName) !== 0) {
            throw new AssertionException('Function is already annotated as used.', 1684230172);
        }
    }

    private function addErrorToPhpcsFile(File $phpcsFile, int $stackPtr, string $functionName): void
    {
        $missingUseStatement = 'use function Safe\\' . $functionName . ';';
        $phpcsFile->addError(
            'Function \'' . $functionName . '\' is not imported from Safe! Add \'' . $missingUseStatement
            . '\' to your uses.',
            $stackPtr,
            'FunctionNotImported'
        );
    }
}
