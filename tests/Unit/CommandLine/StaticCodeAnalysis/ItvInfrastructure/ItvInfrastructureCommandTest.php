<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\ItvInfrastructure;

use DI\Container;
use Mockery;
use Mockery\MockInterface;
use Override;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandFinder;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandNotFoundException;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\ItvInfrastructure\ItvInfrastructureCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\ItvInfrastructure\TerminalCommand;
use Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TargetableToolsCommandTest;

class ItvInfrastructureCommandTest extends TargetableToolsCommandTest
{
    /** @var Container|MockInterface */
    private Container $mockedContainer;
    /** @var MockInterface|NpmCommandFinder */
    private NpmCommandFinder $mockedTerminalCommandFinder;

    #[Override]
    protected function setUp(): void
    {
        $this->terminalCommandName = 'ITV Infrastructure Validation';
        $this->terminalCommandType = TerminalCommand::class;
        $this->exclusionToken = '.dontItvInfrastructure';
        $this->allowedFileEndings = ['microservice.json'];
        $this->mockedTerminalCommand = Mockery::mock(TerminalCommand::class);
        $this->mockedContainer = Mockery::mock(Container::class);
        $this->mockedTerminalCommandFinder = Mockery::mock(NpmCommandFinder::class);

        $this->mockedContainer->shouldReceive('make')->atLeast()->once()
            ->with(TerminalCommand::class)
            ->andReturn($this->mockedTerminalCommand);

        parent::setUp();

        $this->subject = new ItvInfrastructureCommand($this->mockedTargetableInputFacet);
        $this->subject->injectDependenciesToolCommand(
            $this->mockedTerminalCommandRunner,
            $this->mockedEventDispatcher,
        );
        $this->subject->injectDependenciesCommand($this->mockedContainer, $this->mockedTerminalCommandFinder);
    }

    /**
     * @test
     */
    #[Override]
    public function executeRunsTerminalCommand(): void
    {
        $this->mockedTerminalCommandFinder->shouldReceive('findTerminalCommand')->once()
            ->with(TerminalCommand::VALIDATOR);
        parent::executeRunsTerminalCommand();
    }

    /**
     * @test
     */
    #[Override]
    public function executeWrappsException(): void
    {
        $this->mockedTerminalCommandFinder->shouldReceive('findTerminalCommand')->once()
            ->with(TerminalCommand::VALIDATOR);
        parent::executeWrappsException();
    }

    /**
     * @test
     */
    #[Override]
    public function executeWarnsAboutNoUsefulFilesToSniff(): void
    {
        $this->mockedTerminalCommandFinder->shouldReceive('findTerminalCommand')->once()
            ->with(TerminalCommand::VALIDATOR);
        parent::executeWarnsAboutNoUsefulFilesToSniff();
    }

    /**
     * @test
     */
    public function configureSetsCorrectAttributes(): void
    {
        self::assertSame('sca:itv-infrastructure', $this->subject->getName());
        self::assertSame(
            'Validate ITV microservice.json infrastructure definitions.',
            $this->subject->getDescription(),
        );
        self::assertSame(
            'This tool validates infrastructure/microservice.json against the ITV infrastructure '
            . 'schema and the rules the schema cannot express. Repositories without such a file are '
            . 'skipped. Add a .dontItvInfrastructure file to directories that should be ignored.',
            $this->subject->getHelp(),
        );
    }

    /**
     * @test
     */
    public function executeSkipsCommandIfNotFound(): void
    {
        $this->mockedTerminalCommandFinder->shouldReceive('findTerminalCommand')->once()
            ->andThrow(new NpmCommandNotFoundException());

        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with(
                '<info>' . TerminalCommand::VALIDATOR . ' could not be found. '
                . 'To use this sniff please refer to the README.md</info>',
            );

        $result = $this->subject->execute($this->mockedInput, $this->mockedOutput);

        self::assertSame(0, $result);
    }
}
