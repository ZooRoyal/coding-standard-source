<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\ApplicationLifeCycle;

use DI\Container;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\EventDispatcherFactory;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\GitCommandPreconditionChecker;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\TerminalCommandPreconditionChecker;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Exclusion\ExclusionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Extension\FileExtensionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Fix\FixDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Multiprocess\MultiprocessDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\MinimalVersionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Verbose\VerboseDecorator;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

/**
 * Class EventDispatcherFactoryTest
 */
// phpcs:ignore ZooRoyal.TypeHints.LimitUseStatement.TooManyUseStatements
class EventDispatcherFactoryTest extends TestCase
{
    private EventDispatcherFactory $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;
    /** @var array<string> */
    private array $subscribers
        = [
            GitCommandPreconditionChecker::class,
            TerminalCommandPreconditionChecker::class,
            ExclusionDecorator::class,
            FileExtensionDecorator::class,
            FixDecorator::class,
            TargetDecorator::class,
            VerboseDecorator::class,
            MultiprocessDecorator::class,
            MinimalVersionDecorator::class,
            VersionDecorator::class,
        ];

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function build(): void
    {
        $mockedEventDispatcher = Mockery::mock('overload:' . EventDispatcher::class);

        foreach ($this->subscribers as $subscriber) {
            $mockedSubscriber = Mockery::mock($subscriber);
            $this->subjectParameters[Container::class]->shouldReceive('get')->once()
                ->with($subscriber)->andReturn($mockedSubscriber);
            $mockedEventDispatcher->shouldReceive('addSubscriber')->once()
                ->with($mockedSubscriber);
        }

        $result = $this->subject->build();

        /** @phpstan-ignore-next-line */
        self::assertSame($result->mockery_getName(), $mockedEventDispatcher->mockery_getName());
    }

    #[Override]
    public function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(EventDispatcherFactory::class);
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
