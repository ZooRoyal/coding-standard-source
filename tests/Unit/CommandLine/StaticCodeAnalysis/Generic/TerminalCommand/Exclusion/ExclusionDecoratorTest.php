<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\ExclusionListFactory;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class ExclusionDecoratorTest extends TestCase
{
    private ExclusionDecorator $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;
    /** @var MockInterface|ExclusionTerminalCommand */
    private ExclusionTerminalCommand $mockedTerminalCommand;
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
        $this->mockedTerminalCommand = Mockery::mock(ExclusionTerminalCommand::class);
        $this->mockedInput = Mockery::mock(InputInterface::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEvent->shouldReceive('getInput')->andReturn($this->mockedInput);
        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);

        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(
            ExclusionDecorator::class,
        );
        $this->subject = $buildFragments['subject'];
        $this->subjectParameters = $buildFragments['parameters'];
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function decorateAddsExclusionToTerminalCommand(): void
    {
        $forgedMockedRealPath = 'wubwub';
        $forgedToken = '.asdasdqweqwe';
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);
        $mockedExclusionList = [$mockedEnhancedFileInfo];

        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );
        $this->mockedEvent->shouldReceive('getExclusionListToken')->atLeast()->once()->andReturn($forgedToken);

        $this->subjectParameters[ExclusionListFactory::class]->shouldReceive('build')->once()
            ->with($forgedToken)->andReturn($mockedExclusionList);

        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with('<info>Following Paths will be excluded</info>', OutputInterface::VERBOSITY_VERBOSE);
        $mockedEnhancedFileInfo->shouldReceive('getRealPath')->atLeast()->once()->andReturn($forgedMockedRealPath);
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with($forgedMockedRealPath, OutputInterface::VERBOSITY_VERBOSE);
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with('', OutputInterface::VERBOSITY_VERBOSE);

        $this->mockedTerminalCommand->shouldReceive('addExclusions')->once()->with($mockedExclusionList);

        $this->subject->decorate($this->mockedEvent);
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

        $result = ExclusionDecorator::getSubscribedEvents();

        self::assertSame($expectedEvents, $result);
    }
}
