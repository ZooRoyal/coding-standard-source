<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\ExclusionList\Excluders;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\CacheKeyGenerator;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\GitPathsExcluder;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class GitPathsExcluderTest extends TestCase
{
    private GitPathsExcluder $subject;
    private EnhancedFileInfo|MockInterface $forgedRootDirectory;
    /** @var array<MockInterface> */
    private array $subjectParameters;

    protected function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(GitPathsExcluder::class);
        $this->subject = $buildFragments['subject'];
        $this->subjectParameters = $buildFragments['parameters'];

        $this->forgedRootDirectory = Mockery::mock(EnhancedFileInfo::class);

        $this->subjectParameters[Environment::class]->shouldReceive('getRootDirectory')->atMost()->once()
            ->withNoArgs()->andReturn($this->forgedRootDirectory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPathsToExcludeFinderFindsNothing(): void
    {
        $expectedResult = [];

        $this->subjectParameters[CacheKeyGenerator::class]->shouldReceive('generateCacheKey')->once()
            ->with([])->andReturn('asdasdqweqwe12123');

        $this->subjectParameters[FileSearchInterface::class]->shouldReceive('listFolderFiles')->once()
            ->with('.git', $this->forgedRootDirectory, [], 1)->andReturn([]);
        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromArrayOfPaths')
            ->once()->with([])->andReturn([]);

        $result = $this->subject->getPathsToExclude([], []);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getPathsToExcludeFinderFindsSomething(): void
    {
        $mockedExcludes = [Mockery::mock(EnhancedFileInfo::class), Mockery::mock(EnhancedFileInfo::class)];

        $forgedFoundDirectories = [
            $this->forgedRootDirectory . DIRECTORY_SEPARATOR . 'foo',
            $this->forgedRootDirectory . DIRECTORY_SEPARATOR . 'bar',
        ];
        $mockedFoundFile1 = Mockery::mock(EnhancedFileInfo::class);
        $mockedFoundFile2 = Mockery::mock(EnhancedFileInfo::class);
        $mockedFoundFile1->shouldReceive('getPath')->once()->withNoArgs()->andReturn($forgedFoundDirectories[0]);
        $mockedFoundFile2->shouldReceive('getPath')->once()->withNoArgs()->andReturn($forgedFoundDirectories[1]);

        $forgedSearchResult = [$mockedFoundFile1, $mockedFoundFile2];

        $expectedResult = [Mockery::mock(EnhancedFileInfo::class), Mockery::mock(EnhancedFileInfo::class)];

        $this->subjectParameters[CacheKeyGenerator::class]->shouldReceive('generateCacheKey')->atLeast()->once()
            ->with($mockedExcludes)->andReturn('asdasdqweqwe12123');

        $this->subjectParameters[FileSearchInterface::class]->shouldReceive('listFolderFiles')->once()
            ->with('.git', $this->forgedRootDirectory, $mockedExcludes, 1)
            ->andReturn($forgedSearchResult);

        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromArrayOfPaths')
            ->once()->with($forgedFoundDirectories)->andReturn($expectedResult);

        $result1 = $this->subject->getPathsToExclude($mockedExcludes, []);
        $result2 = $this->subject->getPathsToExclude($mockedExcludes, []);

        self::assertSame($expectedResult, $result1);
        self::assertSame($result2, $result1);
    }
}
