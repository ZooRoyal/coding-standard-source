<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\Environment;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

use function Safe\realpath;

class EnvironmentTest extends TestCase
{
    private Environment $subject;
    /** @var array<MockInterface>|array<mixed> */
    private array $subjectParameters;
    private MockInterface|EnhancedFileInfo $mockedEnhancedFileInfo;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(Environment::class);
        $this->subject = $buildFragments['subject'];
        $this->subjectParameters = $buildFragments['parameters'];
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getRootDirectory(): void
    {
        $expectedPath = dirname(__DIR__, 4);

        $this->subjectParameters[ProcessRunner::class]->shouldReceive('runAsProcess')->once()
            ->with('git', 'rev-parse', '--show-toplevel')->andReturn($expectedPath);

        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromPath')->once()
            ->with(realpath(dirname(__DIR__, 4)))->andReturn($this->mockedEnhancedFileInfo);

        $result = $this->subject->getRootDirectory();

        self::assertSame($this->mockedEnhancedFileInfo, $result);
    }

    /**
     * @test
     *
     * @runInSeparateProcess
     * @preserveGlobalState  false
     */
    public function getVendorDirectory(): void
    {
        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromPath')->once()
            ->with(realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'vendor'))
            ->andReturn($this->mockedEnhancedFileInfo);

        $result = $this->subject->getVendorDirectory();

        self::assertSame($this->mockedEnhancedFileInfo, $result);
    }

    /**
     * @test
     */
    public function getPackageDirectory(): void
    {
        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromPath')->once()
            ->with(dirname(__DIR__, 4))->andReturn($this->mockedEnhancedFileInfo);

        $result = $this->subject->getPackageDirectory();

        self::assertSame($this->mockedEnhancedFileInfo, $result);
    }
}
