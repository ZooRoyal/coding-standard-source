<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use DI\Container;
use Mockery;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe\CheckSafeFunctionUsageSniff;

class CheckSafeFunctionUsageSniffTest extends TestCase
{
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
    public function notFoundSafeLibrarySkippsProcess(): void
    {
        $mockedContainerFactory = Mockery::mock('overload:' . ContainerFactory::class);
        $mockedContainer = Mockery::mock(Container::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $mockedFile = Mockery::mock(File::class);

        $mockedContainerFactory->expects()->getContainerInstance()->andReturn($mockedContainer);
        $mockedContainer->expects()->get(Environment::class)->andReturn($mockedEnvironment);
        $mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn('/foo/bar');

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
     */
    public function registerReturnsCorrectTokenArray(): void
    {
        $subject = new CheckSafeFunctionUsageSniff();
        $result = $subject->register();

        self::assertSame([T_STRING], $result);
    }
}
