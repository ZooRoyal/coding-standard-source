<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\FileFinder\AdaptableFileFinder;
use Zooroyal\CodingStandard\CommandLine\FileFinder\GitChangeSet;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\ParentBranchGuesser;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\ToolCommandFacet\TargetableInputFacet;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

/**
 * This is a very busy test and needs all the objects ;/
 */
// phpcs:ignore ZooRoyal.TypeHints.LimitUseStatement.TooManyUseStatements
class TargetDecoratorTest extends TestCase
{
    private TargetDecorator $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;
    /** @var MockInterface|\Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand */
    private TargetTerminalCommand $mockedTerminalCommand;
    /** @var MockInterface|DecorateEvent */
    private DecorateEvent $mockedEvent;
    /** @var MockInterface|InputInterface */
    private InputInterface $mockedInput;
    /** @var MockInterface|OutputInterface */
    private OutputInterface $mockedOutput;
    /** @var array<string> */
    private array $forgedAllowedFileEndings = ['asd', 'qwe'];
    private string $forgedExclusionListToken = 'uptiwubti';

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(TargetTerminalCommand::class);
        $this->mockedInput = Mockery::mock(InputInterface::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEvent->shouldReceive('getInput')->andReturn($this->mockedInput);
        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);
        $this->mockedEvent->shouldReceive('getAllowedFileEndings')->andReturn($this->forgedAllowedFileEndings);
        $this->mockedEvent->shouldReceive('getExclusionListToken')->andReturn($this->forgedExclusionListToken);

        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(
            TargetDecorator::class,
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
     *
     * @dataProvider decorateAddsTargetsToTerminalCommandDataProvider
     */
    public function decorateAddsTargetsToTerminalCommand(
        bool $forgedAutoTarget,
        string|null $forgedTarget,
        ?string $expectedTargetBranch,
    ): void {
        $forgedRealPath = 'asdasd';
        $forgedCommitHash = 'asdasdwqeqwe';
        $mockedGitChangeSet = Mockery::mock(GitChangeSet::class);
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);
        $mockedEnhancedFileInfos = [$mockedEnhancedFileInfo, $mockedEnhancedFileInfo];

        $this->subjectParameters[ParentBranchGuesser::class]->shouldReceive('guessParentBranchAsCommitHash')
            ->withNoArgs()->andReturn($expectedTargetBranch);

        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );

        $this->prepareMockedInputHasOption(true, true);
        $this->prepareMockedInputGetOption($forgedAutoTarget, $forgedTarget);

        $this->prepareTargetedOutput($forgedCommitHash, [$forgedRealPath, $forgedRealPath]);

        $this->subjectParameters[AdaptableFileFinder::class]->shouldReceive('findFiles')->once()
            ->with($this->forgedAllowedFileEndings, $this->forgedExclusionListToken, '', $expectedTargetBranch)
            ->andReturn($mockedGitChangeSet);

        $mockedEnhancedFileInfo->shouldReceive('getRealPath')->twice()->andReturn($forgedRealPath);

        $mockedGitChangeSet->shouldReceive('getCommitHash')->atLeast()->once()->andReturn($forgedCommitHash);
        $mockedGitChangeSet->shouldReceive('getFiles')->atLeast()->once()->andReturn($mockedEnhancedFileInfos);

        $this->mockedTerminalCommand->shouldReceive('addTargets')->once()->with($mockedEnhancedFileInfos);
        $this->subject->decorate($this->mockedEvent);
    }

    /** @return array<string,array<int,bool|string|null>> */
    public function decorateAddsTargetsToTerminalCommandDataProvider(): array
    {
        return [
            'autoTargeting' => [true, null, 'auto/targeted'],
            'targeted' => [false, 'origin/asdqwe', 'origin/asdqwe'],
            'both' => [true, 'origin/asdqwe', 'auto/targeted'],
        ];
    }

    /**
     * @test
     */
    public function decorateShouldNotReactToEmptyTargetedInput(): void
    {
        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );

        $this->prepareMockedInputHasOption(true, true);
        $this->prepareMockedInputGetOption(false, false);

        $this->mockedOutput->shouldReceive('writeln')->never();

        $this->mockedTerminalCommand->shouldReceive('addTargets')->never();

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

        $result = TargetDecorator::getSubscribedEvents();

        self::assertSame($expectedEvents, $result);
    }

    /**
     * Data provider for decorateOnlyTargetsIfTargetOptionsPresent
     *
     * @return array<string,array<string,bool>>
     */
    public function decorateOnlyTargetsIfTargetOptionsPresentDataProvider(): array
    {
        return [
            'only auto' => ['auto' => true, 'target' => false],
            'only target' => ['auto' => false, 'target' => true],
            'auto and target' => ['auto' => true, 'target' => true],
        ];
    }

    /**
     * @test
     * @dataProvider decorateOnlyTargetsIfTargetOptionsPresentDataProvider
     */
    public function decorateOnlyTargetsIfTargetOptionsPresent(bool $auto, bool $target): void
    {
        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );

        $this->mockedInput->shouldIgnoreMissing();

        $this->prepareMockedInputHasOption($auto, $target);

        $this->subjectParameters[ParentBranchGuesser::class]->shouldReceive('guessParentBranchAsCommitHash')->never();

        $this->mockedOutput->shouldReceive('writeln')->never();

        $this->mockedTerminalCommand->shouldReceive('addTargets')->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /**
     * @test
     */
    public function decorateAllTargetsIfNoOptionPresent(): void
    {
        $mockedGitChangeSet = Mockery::mock(GitChangeSet::class);

        $this->mockedEvent->shouldReceive('getTerminalCommand')->atLeast()->once()->andReturn(
            $this->mockedTerminalCommand,
        );

        $this->mockedInput->shouldIgnoreMissing();

        $this->prepareMockedInputHasOption(false, false);

        $this->subjectParameters[ParentBranchGuesser::class]->shouldReceive('guessParentBranchAsCommitHash')->never();

        $this->subjectParameters[AdaptableFileFinder::class]->shouldReceive('findFiles')->once()
            ->with($this->forgedAllowedFileEndings, $this->forgedExclusionListToken, '', null)
            ->andReturn($mockedGitChangeSet);

        $mockedGitChangeSet->shouldReceive('getCommitHash')->atLeast()->once()->andReturn('');
        $mockedGitChangeSet->shouldReceive('getFiles')->atLeast()->once()->andReturn([]);

        $this->prepareNonTargetedOutput([]);

        $this->mockedTerminalCommand->shouldReceive('addTargets')->once()->with([]);

        $this->subject->decorate($this->mockedEvent);
    }

    /**
     * Prepares the mocked output for a not targeted diffed run.
     *
     * @param array<string> $forgedRealPaths
     */
    private function prepareNonTargetedOutput(array $forgedRealPaths): void
    {
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with(
                '<info>No Target was specified so all applicable files are targeted</info>',
                OutputInterface::VERBOSITY_NORMAL,
            );
        $this->prepareOutput($forgedRealPaths);
    }

    /**
     * Prepares the mocked output for a targeted diffed run.
     *
     * @param array<string> $forgedRealPaths
     */
    private function prepareTargetedOutput(string $forgedCommitHash, array $forgedRealPaths): void
    {
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with('<info>Checking diff to ' . $forgedCommitHash . '</info>', OutputInterface::VERBOSITY_NORMAL);
        $this->prepareOutput($forgedRealPaths);
    }

    /**
     * Prepares the mocked output for a diffed run.
     *
     * @param array<string> $forgedRealPaths
     */
    private function prepareOutput(array $forgedRealPaths): void
    {
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with('<info>Following files will be targeted</info>', OutputInterface::VERBOSITY_VERBOSE);
        foreach ($forgedRealPaths as $forgedRealPath) {
            $this->mockedOutput->shouldReceive('writeln')->once()
                ->with($forgedRealPath, OutputInterface::VERBOSITY_VERBOSE);
        }
        $this->mockedOutput->shouldReceive('writeln')->once()->with('');
    }

    private function prepareMockedInputHasOption(bool $auto, bool $target): void
    {
        $this->mockedInput->shouldReceive('hasOption')
            ->with(TargetableInputFacet::OPTION_AUTO_TARGET)->andReturn($auto);
        $this->mockedInput->shouldReceive('hasOption')
            ->with(TargetableInputFacet::OPTION_TARGET)->andReturn($target);
    }

    /**
     * Prepare mocked Input to return given values when asked.
     */
    private function prepareMockedInputGetOption(bool $forgedAutoTarget, string|false|null $forgedTarget): void
    {
        $this->mockedInput->shouldReceive('getOption')->once()
            ->with(TargetableInputFacet::OPTION_AUTO_TARGET)->andReturn($forgedAutoTarget);
        $this->mockedInput->shouldReceive('getOption')->once()
            ->with(TargetableInputFacet::OPTION_TARGET)->andReturn($forgedTarget);
    }
}
