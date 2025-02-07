<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandFinder;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandNotFoundException;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class NpmCommandFinderTest extends TestCase
{
    private NpmCommandFinder $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;

    #[Override]
    protected function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(
            NpmCommandFinder::class,
        );
        $this->subject = $buildFragments['subject'];
        $this->subjectParameters = $buildFragments['parameters'];
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function findTerminalCommandReturnsCommandIfFound(): void
    {
        $forgedCommand = 'bnlablalbal';
        $forgedVendorDirectory = '/some/path';
        $expectedCommand = 'npx --no-install ' . $forgedCommand;
        $mockedProcess = Mockery::mock(Process::class);

        $this->subjectParameters[Environment::class]->shouldReceive('getVendorDirectory->getRealPath')
            ->once()->withNoArgs()->andReturn($forgedVendorDirectory);

        $this->subjectParameters[ProcessRunner::class]->shouldReceive('runAsProcessReturningProcessObject')
            ->once()
            ->with('npx --prefix ' . $forgedVendorDirectory . '/.. --no-install ' . $forgedCommand . ' --help')
            ->andReturn($mockedProcess);

        $mockedProcess->shouldReceive('getExitCode')->once()->withNoArgs()->andReturn(0);

        $result = $this->subject->findTerminalCommand($forgedCommand);

        self::assertSame($expectedCommand, $result);
    }

    /**
     * @test
     */
    public function findTerminalCommandThrowsExceptionIfCommandNotFound(): void
    {
        $forgedVendorDirectory = '/some/path';
        $forgedCommand = 'bnlablalbal';
        $mockedProcess = Mockery::mock(Process::class);

        $this->subjectParameters[Environment::class]->shouldReceive('getVendorDirectory->getRealPath')
            ->once()->withNoArgs()->andReturn($forgedVendorDirectory);

        $this->subjectParameters[ProcessRunner::class]->shouldReceive('runAsProcessReturningProcessObject')
            ->once()
            ->with('npx --prefix ' . $forgedVendorDirectory . '/.. --no-install ' . $forgedCommand . ' --help')
            ->andReturn($mockedProcess);

        $mockedProcess->shouldReceive('getExitCode')->once()->withNoArgs()->andReturn(127);

        $this->expectException(
            NpmCommandNotFoundException::class,
        );
        $this->expectExceptionCode(1595949828);
        $this->expectExceptionMessage('Bnlablalbal could not be found in path or by npm.');

        $this->subject->findTerminalCommand($forgedCommand);
    }
}
