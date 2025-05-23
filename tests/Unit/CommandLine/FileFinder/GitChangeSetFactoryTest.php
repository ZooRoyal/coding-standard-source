<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\FileFinder;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\FileFinder\GitChangeSetFactory;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class GitChangeSetFactoryTest extends TestCase
{
    private GitChangeSetFactory $subject;
    /** @var array<MockInterface>  */
    private array $subjectParameters;

    #[Override]
    protected function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(
            GitChangeSetFactory::class,
        );
        $this->subject = $buildFragments['subject'];
        $this->subjectParameters = $buildFragments['parameters'];
    }

    /**
     * @test
     */
    public function buildReturns(): void
    {
        $forgedFiles = ['asd', 'qwe'];
        $expectedCommitHash = 'asdasdasd1223213';
        $forgedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $this->subjectParameters[EnhancedFileInfoFactory::class]->shouldReceive('buildFromArrayOfPaths')->once()
            ->with($forgedFiles)->andReturn([$forgedEnhancedFileInfo]);

        $result = $this->subject->build($forgedFiles, $expectedCommitHash);

        $resultingFiles = $result->getFiles();
        $resultingCommitHash = $result->getCommitHash();

        self::assertSame([$forgedEnhancedFileInfo], $resultingFiles);
        self::assertSame($expectedCommitHash, $resultingCommitHash);
    }
}
