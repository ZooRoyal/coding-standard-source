<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use DI\Container;
use Mockery;
use Override;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Safe\Exceptions\DirException;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe\CheckSafeFunctionUsageSniff;

class CheckSafeFunctionUsageSniffTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function constructTheSniff(): void
    {
        $subject = new CheckSafeFunctionUsageSniff();
        self::assertInstanceOf(Sniff::class, $subject);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function notFoundSafeLibrarySkipsProcess(): void
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
        $mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn('/foo/bar');
        $mockedFilesystem->expects()->exists('/foo/bar/vendor/thecodingmachine/safe/generated/8.4/')->andReturn(true);

        $mockedComposerInterpreter->expects()->getMinimalViablePhpVersion()->andReturn('8.4.3');

        $this->expectExceptionObject(
            new RuntimeException(
                'No function names found! Did you forget to install thecodingmachine/Safe?',
                1684240278,
            ),
        );

        $subject = new CheckSafeFunctionUsageSniff();
        $subject->process($mockedFile, 0);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function notFoundSafeVersionLibraryThrowsError(): void
    {
        $mockedContainerFactory = Mockery::mock('overload:' . ContainerFactory::class);
        $mockedContainer = Mockery::mock(Container::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);
        $mockedFilesystem = mock(Filesystem::class);

        $mockedContainerFactory->expects()->getContainerInstance()->andReturn($mockedContainer);
        $mockedContainer->expects()->get(Environment::class)->andReturn($mockedEnvironment);
        $mockedContainer->expects()->get(ComposerInterpreter::class)->andReturn($mockedComposerInterpreter);
        $mockedComposerInterpreter->expects()->getMinimalViablePhpVersion()->andReturn('8.4.3');
        $mockedContainer->expects()->get(Filesystem::class)->andReturn($mockedFilesystem);
        $mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn('/foo/bar');
        $mockedFilesystem->expects()->exists('/foo/bar/vendor/thecodingmachine/safe/generated/8.4/')->andReturn(false);

        try {
            new CheckSafeFunctionUsageSniff();
        } catch (DirException $exception) {
            self::assertSame(
                'Path "/foo/bar/vendor/thecodingmachine/safe/generated/8.4/" does not exist! '
                . 'Did you install thecodingmachine/safe >= 3',
                $exception->getMessage()
            );
            self::assertSame(1742901391, $exception->getCode());
        }
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
}
