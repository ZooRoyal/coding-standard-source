<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\Process;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;

use function Safe\getcwd;

class ProcessRunnerTest extends TestCase
{
    private ProcessRunner $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->subject = new ProcessRunner();
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function createProcessCreatesNewProcess(): void
    {
        $overwrittenProcess = Mockery::mock('overload:' . Process::class);

        $overwrittenProcess->shouldReceive('__construct')->once()->with(['ls'], getcwd());
        $overwrittenProcess->shouldReceive('setIdleTimeout')->once()->with(120);
        $overwrittenProcess->shouldReceive('setTimeout')->once()->with(null);

        $this->subject->createProcess('ls');
    }

    /**
     * @test
     */
    public function runAsProcess(): void
    {
        $result = $this->subject->runAsProcess('ls');
        self::assertIsString($result);
    }

    /**
     * @test
     */
    public function runAsProcessReturningProcessObject(): void
    {
        $expectedResult = $this->subject->runAsProcess('ls');

        $result = $this->subject->runAsProcessReturningProcessObject('ls');

        self::assertInstanceOf(Process::class, $result);
        self::assertSame($expectedResult, trim($result->getOutput()));
    }

    /**
     * @test
     */
    public function runAsProcessReturningProcessObjectWithArgumentsInjection(): void
    {
        $this->expectException(ProcessFailedException::class);
        $this->subject->runAsProcess('git', 'version\'; ls');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function runProcessWithArguments(): void
    {
        $result = $this->subject->runAsProcess('git', 'version');

        MatcherAssert::assertThat($result, Matchers::startsWith('git version'));
    }

    /**
     * @test
     */
    public function runProcessWithArgumentsInjection(): void
    {
        $this->expectException(ProcessFailedException::class);
        $this->subject->runAsProcess('git', 'version\'; ls');
    }
}
