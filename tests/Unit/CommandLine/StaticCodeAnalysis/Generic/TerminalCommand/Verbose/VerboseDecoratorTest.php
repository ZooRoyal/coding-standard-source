<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseTerminalCommand;

class VerboseDecoratorTest extends TestCase
{
    private VerboseDecorator $subject;
    /** @var MockInterface|VerboseTerminalCommand */
    private VerboseTerminalCommand $mockedTerminalCommand;
    /** @var MockInterface|DecorateEvent */
    private DecorateEvent $mockedEvent;
    /** @var MockInterface|InputInterface */
    private InputInterface $mockedInput;
    /** @var MockInterface|OutputInterface */
    private OutputInterface $mockedOutput;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(VerboseTerminalCommand::class);
        $this->mockedInput = Mockery::mock(InputInterface::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEvent->shouldReceive('getInput')->andReturn($this->mockedInput);
        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);

        $this->subject = new VerboseDecorator();
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider decoradeAddsVerboseFlagIfApplicableDataProvider
     */
    public function decorateAddsVerboseFlagIfApplicable(
        bool $isVerbose,
        bool $isQuiet,
        int $verboseRuns,
        int $quietRuns,
    ): void {
        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );

        $this->mockedInput->shouldReceive('getOption')->atLeast()->once()->with('verbose')->andReturn($isVerbose);
        $this->mockedInput->shouldReceive('getOption')->times($quietRuns)->with('quiet')->andReturn($isQuiet);
        $this->mockedOutput->expects('getVerbosity')->times($verboseRuns)->andReturn(OutputInterface::VERBOSITY_VERBOSE);

        $this->mockedTerminalCommand->shouldReceive('addVerbosityLevel')->times($verboseRuns)
            ->with(OutputInterface::VERBOSITY_VERBOSE);
        $this->mockedTerminalCommand->shouldReceive('addVerbosityLevel')->times($quietRuns)
            ->with(OutputInterface::VERBOSITY_QUIET);

        $this->mockedOutput->shouldReceive('writeln')->times($verboseRuns)
            ->with('<info>Command will be executed verbosely</info>' . PHP_EOL, OutputInterface::VERBOSITY_VERBOSE);

        $this->subject->decorate($this->mockedEvent);
    }

    /** @return array<string,array<int,bool|int>> */
    public function decoradeAddsVerboseFlagIfApplicableDataProvider(): array
    {
        return [
            'verbose' => [true, false, 1, 0],
            'quiet' => [false, true, 0, 1],
            'booth' => [true, true, 1, 0],
        ];
    }

    /**
     * @test
     */
    public function decorateShouldNotReactToOtherTerminalCommands(): void
    {
        $mockedTerminalCommand = Mockery::mock(TerminalCommand::class);
        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn($mockedTerminalCommand);

        $this->mockedTerminalCommand->shouldReceive('addExclusions')->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /**
     * @test
     */
    public function getSubscribedEventsReturnsExpectedEvents(): void
    {
        $expectedEvents = [TerminalCommandDecorator::EVENT_DECORATE_TERMINAL_COMMAND => ['decorate', 50]];

        $result = VerboseDecorator::getSubscribedEvents();

        self::assertSame($expectedEvents, $result);
    }
}
