<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand;

use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandRunner;

class TerminalCommandRunnerTest extends TestCase
{
    private TerminalCommandRunner $subject;
    /** @var MockInterface|TerminalCommand */
    private TerminalCommand $mockedTerminalCommand;
    /** @var MockInterface|OutputInterface */
    private OutputInterface $mockedOutput;
    private ProcessRunner $processRunner;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedOutput = Mockery::mock(OutputInterface::class);
        $this->mockedTerminalCommand = Mockery::mock(TerminalCommand::class);
        $this->processRunner = new ProcessRunner();
        $this->subject = new TerminalCommandRunner($this->processRunner, $this->mockedOutput);
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @medium
     */
    public function runRelaysOutputOfCommand(): void
    {
        $this->mockedOutput->shouldReceive('write')->once()->with(
            Matchers::containsString('"feed"'),
            false,
            Output::OUTPUT_RAW,
        );

        $this->mockedOutput->shouldReceive('write')->atLeast()->once()
            ->with(Mockery::any(), false, Output::OUTPUT_RAW);

        $this->mockedTerminalCommand->shouldReceive('toArray')->andReturn(['curl', 'https://lipsum.com/feed/json']);
        $this->subject->run($this->mockedTerminalCommand);
    }

    /**
     * @test
     */
    public function runRelaysErrorOutputOfCommand(): void
    {
        $this->mockedOutput->shouldReceive('write')->atLeast()->once()
            ->with(Mockery::any(), false, Output::OUTPUT_RAW);

        $this->mockedTerminalCommand->shouldReceive('toArray')->andReturn(['ls', 'asdqwesss']);
        $this->subject->run($this->mockedTerminalCommand);
    }

    /**
     * @test
     * @dataProvider runReturnsExitCodeDataProvider
     *
     * @param array<string> $command
     */
    public function runReturnsExitCode(array $command, int $exitCode): void
    {
        $this->mockedTerminalCommand->shouldReceive('toArray')->andReturn($command);
        $result = $this->subject->run($this->mockedTerminalCommand);

        self::assertSame($exitCode, $result);
    }

    /** @return array<string,array<array<string>|int>> */
    public function runReturnsExitCodeDataProvider(): array
    {
        return [
            'success' => [['true'], 0],
            'failure' => [['false'], 1],
        ];
    }
}
