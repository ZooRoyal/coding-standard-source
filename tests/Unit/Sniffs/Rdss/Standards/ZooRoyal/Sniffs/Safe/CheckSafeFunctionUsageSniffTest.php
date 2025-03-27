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
        $mockedFilesystem->expects()->exists($environmentPath . '/vendor/thecodingmachine/safe/generated/8.4/')
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
     */
    public function registerReturnsCorrectTokenArray(): void
    {
        $subject = new CheckSafeFunctionUsageSniff();
        $result = $subject->register();

        self::assertSame([T_STRING], $result);
    }
}
