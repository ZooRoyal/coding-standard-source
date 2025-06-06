<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\ExclusionList\Excluders;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\StaticExcluder;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class StaticExcluderTest extends TestCase
{
    private StaticExcluder $subject;
    /** @var array<MockInterface> */
    private array $subjectParameters;

    #[Override]
    protected function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(StaticExcluder::class);
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
    public function getPathsToExclude(): void
    {
        $expectedExclusionPaths = [
            '.git',
            '.idea',
            '.vagrant',
            'node_modules',
            'vendor',
            'bower_components',
            '.pnpm',
            '.pnpm-store',
        ];

        $forgedResult = [Mockery::mock(EnhancedFileInfo::class), Mockery::mock(EnhancedFileInfo::class)];

        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromArrayOfPaths')
            ->once()->with($expectedExclusionPaths)->andReturn($forgedResult);

        $result = $this->subject->getPathsToExclude([]);
        self::assertSame($forgedResult, $result);
    }

    /**
     * @test
     */
    public function getPathsToExcludeWithWorkingCache(): void
    {
        $expectedExclusionPaths = [
            '.git',
            '.idea',
            '.vagrant',
            'node_modules',
            'vendor',
            'bower_components',
            '.pnpm',
            '.pnpm-store',
        ];

        $forgedResult = [Mockery::mock(EnhancedFileInfo::class), Mockery::mock(EnhancedFileInfo::class)];

        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromArrayOfPaths')
            ->once()->with($expectedExclusionPaths)->andReturn($forgedResult);

        $this->subject->getPathsToExclude([]);
        $result = $this->subject->getPathsToExclude([]);
        self::assertSame($forgedResult, $result);
    }
}
