<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use DI\Container;
use Mockery;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Override;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe\CheckSafeFunctionUsageSniff;

// phpcs:ignore ZooRoyal.TypeHints.LimitUseStatement.TooManyUseStatements
class CheckSafeFunctionUsageSniffTest extends TestCase
{
    private const string SAFE_VENDOR_PATH = '/vendor/thecodingmachine/safe/generated/8.4/';

    private vfsStreamDirectory $vfsRootDirectory;
    private vfsStreamDirectory $vfsSafeDirectory;

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsRootDirectory = vfsStream::setup('checkSafe');
        $this->vfsSafeDirectory = vfsStream::newDirectory(self::SAFE_VENDOR_PATH, 0777);
        $this->vfsRootDirectory->addChild($this->vfsSafeDirectory);
    }

    /**
     * @test
     */
    public function constructTheSniff(): void
    {
        $subject = new CheckSafeFunctionUsageSniff();
        self::assertInstanceOf(Sniff::class, $subject);
    }

    private function prepareMocksForAssertNextTokenParenthesisOpener(
        MockInterface&File $mockedFile,
        int $forgedPointer,
    ): MockInterface&TokenHelper {
        // Mocks for assertNextTokenParenthesisOpener
        $mockedTokenHelper = Mockery::mock('overload:' . TokenHelper::class);
        $mockedTokenHelper->expects()->findNextEffective($mockedFile, $forgedPointer + 1)
            ->andReturn($forgedPointer + 1);
        return $mockedTokenHelper;
    }

    private function prepareMocksForAssertFunctionUnused(MockInterface&File $mockedFile): void
    {
        // Mocks for assertFunctionUnused
        $mockedUseStatementFunctionUninteresting = Mockery::mock(UseStatement::class);
        $mockedUseStatement = Mockery::mock(UseStatement::class);
        $forgedUseStatementResults = [
            17 => [
                'overwrite' => $mockedUseStatementFunctionUninteresting,
                'sniff' => $mockedUseStatement,
            ],
        ];
        $mockedUseStatementHelper = Mockery::mock('overload:' . UseStatementHelper::class);
        $mockedUseStatementHelper->expects()->getFileUseStatements($mockedFile)->andReturn($forgedUseStatementResults);
        $mockedUseStatementFunctionUninteresting->expects()->getType()->andReturn('function');
        $mockedUseStatement->expects()->getType()->andReturn('no function');
        $mockedUseStatementFunctionUninteresting->expects()->getNameAsReferencedInFile()->andReturn('argh');
    }

    private function prepareMocksForAssertGlobalFunctionCall(
        MockInterface&TokenHelper $mockedTokenHelper,
        MockInterface&File $mockedFile,
        int $forgedPointer,
    ): void {
        // Mocks for assertGlobalFunctionCall
        $mockedTokenHelper->expects()->findPreviousEffective($mockedFile, $forgedPointer - 1)
            ->andReturn($forgedPointer - 1);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     * @dataProvider         notFoundSafeLibrarySkipsProcessDataProvider
     */
    public function notFoundSafeLibrarySkipsProcess(string $environmentPath, bool $dirExists): void
    {
        $mockedContainerFactory = Mockery::mock('overload:' . ContainerFactory::class);
        $mockedContainer = Mockery::mock(Container::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $mockedFile = Mockery::mock(File::class);
        $mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);
        $mockedFilesystem = mock(Filesystem::class);

        $mockedContainerFactory->expects()->getContainerInstance()->andReturn($mockedContainer);
        $mockedContainer->expects()->get(Environment::class)->andReturn($mockedEnvironment);
        $mockedContainer->expects()->get(ComposerInterpreter::class)->andReturn($mockedComposerInterpreter);
        $mockedContainer->expects()->get(Filesystem::class)->andReturn($mockedFilesystem);
        $mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn($environmentPath);
        $mockedFilesystem->expects()->exists($environmentPath . self::SAFE_VENDOR_PATH)
            ->andReturn($dirExists);

        $mockedComposerInterpreter->expects()->getMinimalViablePhpVersion()->andReturn('8.4.3');

        $this->expectExceptionObject(
            new RuntimeException(
                'No function names found! Did you forget to install thecodingmachine/Safe ^v3?',
                1684240278,
            ),
        );

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, 0);
    }

    /** @return array<string,array<string,bool|string>> */
    public function notFoundSafeLibrarySkipsProcessDataProvider(): array
    {
        return [
            'no Path' => ['environmentPath' => '/foo/bar', 'dirExists' => true],
            'scandir empty' => ['environmentPath' => __DIR__, 'dirExists' => false],
        ];
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function processAddsError(): void
    {
        $forgedPointer = 100;
        $forgedSafeFunctionName = 'foo';
        $mockedFile = $this->prepareMocksForConstruction(['foo.php' =>  '<?php function ' . $forgedSafeFunctionName . '() {}']);

        $mockedFile->allows()->getTokens()->andReturn(
            [
                $forgedPointer - 1 => ['code' => T_WHITESPACE],
                $forgedPointer => ['code' => T_STRING, 'content' => 'Foo'],
                $forgedPointer + 1 => ['code' => T_OPEN_PARENTHESIS],
            ],
        );
        $mockedTokenHelper = $this->prepareMocksForAssertNextTokenParenthesisOpener($mockedFile, $forgedPointer);
        $this->prepareMocksForAssertGlobalFunctionCall($mockedTokenHelper, $mockedFile, $forgedPointer);
        $this->prepareMocksForAssertFunctionUnused($mockedFile);

        // Finish with the key assertion

        $mockedFile->expects()->addError(
            'Function \'' . $forgedSafeFunctionName
            . '\' is not imported from Safe! Add \'use function Safe\foo;\' to your uses.',
            $forgedPointer,
            'FunctionNotImported'
        );

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, $forgedPointer);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function processAddsNoErrorBecauseOfParenthesisCheck(): void
    {
        $forgedPointer = 100;
        $forgedSafeFunctionName = 'foo';
        $mockedFile = $this->prepareMocksForConstruction(['foo.php' =>  '<?php function ' . $forgedSafeFunctionName . '() {}']);

        $mockedFile->allows()->getTokens()->andReturn(
            [
                $forgedPointer - 1 => ['code' => T_OBJECT_OPERATOR],
                $forgedPointer => ['code' => T_STRING, 'content' => 'Foo'],
                $forgedPointer + 1 => ['code' => T_STRING],
            ],
        );

        $mockedTokenHelper = Mockery::mock('overload:' . TokenHelper::class);
        $mockedTokenHelper->expects()->findNextEffective($mockedFile, $forgedPointer + 1)
            ->andReturn($forgedPointer + 1);

        $mockedFile->shouldNotReceive('addError');

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, $forgedPointer);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function processAddsNoErrorBecauseGlobalFunction(): void
    {
        $forgedPointer = 100;
        $forgedSafeFunctionName = 'foo';
        $mockedFile = $this->prepareMocksForConstruction(['foo.php' =>  '<?php function ' . $forgedSafeFunctionName . '() {}']);

        $mockedFile->allows()->getTokens()->andReturn(
            [
                $forgedPointer - 1 => ['code' => T_FUNCTION],
                $forgedPointer => ['code' => T_STRING, 'content' => 'Foo'],
                $forgedPointer + 1 => ['code' => T_OPEN_PARENTHESIS],
            ],
        );

        $mockedTokenHelper = $this->prepareMocksForAssertNextTokenParenthesisOpener($mockedFile, $forgedPointer);
        $mockedTokenHelper->expects()->findPreviousEffective($mockedFile, $forgedPointer - 1)
            ->andReturn($forgedPointer - 1);

        $mockedFile->shouldNotReceive('addError');

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, $forgedPointer);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function processAddsNoErrorBecauseNotSafeFunction(): void
    {
        $forgedPointer = 100;
        $forgedSafeFunctionName = 'foo';
        $mockedFile = $this->prepareMocksForConstruction(['foo.php' =>  '<?php function ' . $forgedSafeFunctionName . '() {}']);

        $mockedFile->allows()->getTokens()->andReturn(
            [
                $forgedPointer - 1 => ['code' => T_WHITESPACE],
                $forgedPointer => ['code' => T_STRING, 'content' => 'Woo'],
                $forgedPointer + 1 => ['code' => T_OPEN_PARENTHESIS],
            ],
        );
        $mockedTokenHelper = $this->prepareMocksForAssertNextTokenParenthesisOpener($mockedFile, $forgedPointer);
        $this->prepareMocksForAssertGlobalFunctionCall($mockedTokenHelper, $mockedFile, $forgedPointer);

        // Finish with the key assertion

        $mockedFile->shouldNotReceive('addError');

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, $forgedPointer);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function processAddsNoErrorBecauseFunctionUsed(): void
    {
        $forgedPointer = 100;
        $forgedSafeFunctionName = 'foo';
        $mockedFile = $this->prepareMocksForConstruction(['foo.php' =>  '<?php function ' . $forgedSafeFunctionName . '() {}']);

        $mockedFile->allows()->getTokens()->andReturn(
            [
                $forgedPointer - 1 => ['code' => T_WHITESPACE],
                $forgedPointer => ['code' => T_STRING, 'content' => 'Foo'],
                $forgedPointer + 1 => ['code' => T_OPEN_PARENTHESIS],
            ],
        );
        $mockedTokenHelper = $this->prepareMocksForAssertNextTokenParenthesisOpener($mockedFile, $forgedPointer);
        $this->prepareMocksForAssertGlobalFunctionCall($mockedTokenHelper, $mockedFile, $forgedPointer);

        $mockedUseStatement = Mockery::mock(UseStatement::class);
        $forgedUseStatementResults = [
            17 => [
                'foo' => $mockedUseStatement,
            ],
        ];
        $mockedUseStatementHelper = Mockery::mock('overload:' . UseStatementHelper::class);
        $mockedUseStatementHelper->expects()->getFileUseStatements($mockedFile)->andReturn($forgedUseStatementResults);
        $mockedUseStatement->expects()->getType()->andReturn('function');
        $mockedUseStatement->expects()->getNameAsReferencedInFile()->andReturn($forgedSafeFunctionName);

        // Finish with the key assertion

        $mockedFile->shouldNotReceive('addError');

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, $forgedPointer);
    }

    /**
     * @test
     */
    public function registerReturnsCorrectTokenArray(): void
    {
        $subject = new CheckSafeFunctionUsageSniff();
        $result = $subject->register();

        self::assertSame([T_STRING], $result);
    }

    /** @param array<string,string> $safeFiles */
    private function prepareMocksForConstruction(array $safeFiles): MockInterface&File
    {
        $environmentPath = vfsStream::url('checkSafe');

        $mockedContainerFactory = Mockery::mock('overload:' . ContainerFactory::class);
        $mockedContainer = Mockery::mock(Container::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $mockedFile = Mockery::mock(File::class);
        $mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);
        $mockedFilesystem = mock(Filesystem::class);

        $mockedContainerFactory->expects()->getContainerInstance()->andReturn($mockedContainer);
        $mockedComposerInterpreter->expects()->getMinimalViablePhpVersion()->andReturn('8.4.3');
        $mockedContainer->expects()->get(Environment::class)->andReturn($mockedEnvironment);
        $mockedContainer->expects()->get(ComposerInterpreter::class)->andReturn($mockedComposerInterpreter);
        $mockedContainer->expects()->get(Filesystem::class)->andReturn($mockedFilesystem);
        $mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn($environmentPath);
        $mockedFilesystem->expects()->exists($environmentPath . self::SAFE_VENDOR_PATH)
            ->andReturn(true);

        vfsStream::create(
            ['vendor' => ['thecodingmachine' => ['safe' => ['generated' => ['8.4' => $safeFiles]]]]],
            $this->vfsRootDirectory
        );

        $mockedContainer->expects()->get(Parser::class)
            ->andReturn(new ParserFactory()->createForVersion(PhpVersion::fromString('8.4')));
        $mockedContainer->expects()->get(NodeFinder::class)->andReturn(new NodeFinder());

        return $mockedFile;
    }
}
