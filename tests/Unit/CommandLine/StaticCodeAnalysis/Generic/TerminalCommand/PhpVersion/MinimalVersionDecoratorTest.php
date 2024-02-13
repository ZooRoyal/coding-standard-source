<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ConstraintToVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\MinimalVersionDecorator;
// phpcs:ignore -- I did not find a way to either break this line or to make it shorter.
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\MinimalVersionDependantTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class MinimalVersionDecoratorTest extends TestCase
{
    private MockInterface&ConstraintToVersionConverter $mockedConstraintToVersionConverter;
    private MockInterface&ComposerInterpreter $mockedComposerInterpreter;
    private MockInterface&DecorateEvent $mockedEvent;
    private MockInterface&MinimalVersionDependantTerminalCommand $mockedTerminalCommand;
    private MockInterface&OutputInterface $mockedOutput;
    private MinimalVersionDecorator $subject;

    protected function setUp(): void
    {
        $this->mockedConstraintToVersionConverter = Mockery::mock(ConstraintToVersionConverter::class);
        $this->mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(MinimalVersionDependantTerminalCommand::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEvent->allows()->getOutput()->andReturn($this->mockedOutput);

        $this->subject = new MinimalVersionDecorator(
            $this->mockedConstraintToVersionConverter,
            $this->mockedComposerInterpreter
        );
    }

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
        $this->mockedEvent->allows()->getTerminalCommand()->andReturn($mockedTerminalCommand);

        $this->mockedTerminalCommand->expects()->setMinimalPhpVersion('7.4.33')->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /**
     * @test
     */
    public function decorate(): void
    {
        $this->mockedEvent->allows()->getTerminalCommand()->andReturn($this->mockedTerminalCommand);
        $this->mockedComposerInterpreter->expects()->getLocalPhpVersionConstraint()->once()->andReturn('7.4.*');

        $this->mockedConstraintToVersionConverter->allows()
            ->extractActualPhpVersion('7.4.*')->andReturn('7.4.33');

        $this->mockedTerminalCommand->expects()->setMinimalPhpVersion('7.4.33')->twice();
        $this->mockedOutput->expects()->writeln(
            '<info>Targeted minimal PHP version is 7.4.33</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE
        )->twice();

        $this->subject->decorate($this->mockedEvent);
        $this->subject->decorate($this->mockedEvent);
    }
}
