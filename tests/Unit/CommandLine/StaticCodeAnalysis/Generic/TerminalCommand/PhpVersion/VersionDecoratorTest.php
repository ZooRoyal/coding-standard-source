<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecoratorTest extends TestCase
{
    private MockInterface&ComposerInterpreter $mockedComposerInterpreter;
    private MockInterface&DecorateEvent $mockedEvent;
    private MockInterface&VersionDependentTerminalCommand $mockedTerminalCommand;
    private MockInterface&OutputInterface $mockedOutput;
    private VersionDecorator $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(VersionDependentTerminalCommand::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);
        $this->mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);

        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);

        $this->subject = new VersionDecorator(
            $this->mockedComposerInterpreter,
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function isInstanceOfTerminalCommandDecorator(): void
    {
        self::assertInstanceOf(TerminalCommandDecorator::class, $this->subject);
    }

    /**
     * @test
     */
    public function skipOnWrongTerminalCommand(): void
    {
        $mockedTerminalCommand = Mockery::mock(TerminalCommand::class);
        $this->mockedEvent->expects()->getTerminalCommand()->andReturns($mockedTerminalCommand);
        $this->mockedTerminalCommand->allows()->setPhpVersion(self::anything())->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /**
     * @test
     */
    public function setVersions(): void
    {
        $expectedVersion = '7.4.1';

        $this->mockedEvent->expects()->getTerminalCommand()->andReturn($this->mockedTerminalCommand);

        $this->mockedComposerInterpreter->expects()->getMinimalViablePhpVersion()->andReturn($expectedVersion);

        $this->mockedTerminalCommand->expects()->setPhpVersion($expectedVersion);

        $this->mockedOutput->expects()->writeln(
            '<info>Targeted PHP version is ' . $expectedVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $this->subject->decorate($this->mockedEvent);
    }
}
