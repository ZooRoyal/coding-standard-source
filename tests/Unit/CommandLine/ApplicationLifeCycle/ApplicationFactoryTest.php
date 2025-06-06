<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\ApplicationLifeCycle;

use DI\Container;
use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ApplicationFactory;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\AllToolsCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\FindFilesToCheckCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\JSESLint\JSESLintCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\JSStyleLint\JSStyleLintCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPCodeSniffer\PHPCodeSnifferCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPCopyPasteDetector\PHPCopyPasteDetectorCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPParallelLint\PHPParallelLintCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan\PHPStanCommand;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

/**
 * Class ApplicationFactoryTest
 */
// phpcs:ignore ZooRoyal.TypeHints.LimitUseStatement.TooManyUseStatements
class ApplicationFactoryTest extends TestCase
{
    private ApplicationFactory $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;
    /** @var array<string> */
    private array $commands
        = [
            PHPParallelLintCommand::class,
            PHPCodeSnifferCommand::class,
            PHPStanCommand::class,
            FindFilesToCheckCommand::class,
            PHPCopyPasteDetectorCommand::class,
            JSESLintCommand::class,
            JSStyleLintCommand::class,
            AllToolsCommand::class,
        ];

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function build(): void
    {
        $mockedApplication = Mockery::mock('overload:' . Application::class);
        $mockedCommand = Mockery::mock(Command::class);

        $mockedApplication->shouldReceive('setDispatcher')->once()
            ->with($this->subjectParameters[EventDispatcherInterface::class]);

        $this->subjectParameters[Container::class]->shouldReceive('get')
            ->with(Matchers::anyOf(...$this->commands))->andReturn($mockedCommand);
        $mockedApplication->shouldReceive('add')->times(count($this->commands))
            ->with($mockedCommand);

        $result = $this->subject->build();

        /** @phpstan-ignore-next-line */
        self::assertSame($result->mockery_getName(), $mockedApplication->mockery_getName());
    }

    #[Override]
    public function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(
            ApplicationFactory::class,
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
}
