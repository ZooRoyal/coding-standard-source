<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\FileFinder;

use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Zooroyal\CodingStandard\CommandLine\FileFinder\AdaptableFileFinder;
use Zooroyal\CodingStandard\CommandLine\FileFinder\AllCheckableFileFinder;
use Zooroyal\CodingStandard\CommandLine\FileFinder\CommitishComparator;
use Zooroyal\CodingStandard\CommandLine\FileFinder\DiffCheckableFileFinder;
use Zooroyal\CodingStandard\CommandLine\FileFinder\GitChangeSet;
use Zooroyal\CodingStandard\CommandLine\FileFinder\GitInputValidator;
use Zooroyal\CodingStandard\Tests\Tools\SubjectFactory;

class AdaptableFileFinderTest extends TestCase
{
    /** @var array<MockInterface> */
    private array $subjectParameters;
    private AdaptableFileFinder $subject;

    #[Override]
    protected function setUp(): void
    {
        $subjectFactory = new SubjectFactory();
        $buildFragments = $subjectFactory->buildSubject(AdaptableFileFinder::class);
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
    public function findFilesWithInvalidTargetThrowsException(): void
    {
        $mockedTargetBranchInput = 'blaaaa';
        $this->expectExceptionObject(
            new InvalidArgumentException('Target ' . $mockedTargetBranchInput . ' is no valid commit-ish.', 1553766210),
        );
        $this->subjectParameters[GitInputValidator::class]->shouldReceive('isCommitishValid')
            ->with($mockedTargetBranchInput)->andReturn(false);

        $this->subject->findFiles([], '', '', $mockedTargetBranchInput);
    }

    /**
     * Data Provider for findFilesCallsAllCheckableFileFinder.
     *
     * @return array<string,array<string,bool|class-string|string|null>>
     */
    public function findFilesCallsAllCheckableFileFinderDataProvider(): array
    {
        return [
            'targetBranch' => [
                'targetBranchInput' => 'bla',
                'isCommitishValid' => true,
                'isLocalBranch' => false,
                'finder' => DiffCheckableFileFinder::class,
            ],
            'isLocalBranch' => [
                'targetBranchInput' => 'blarg',
                'isCommitishValid' => true,
                'isLocalBranch' => true,
                'finder' => AllCheckableFileFinder::class,
            ],
            'none' => [
                'targetBranchInput' => null,
                'isCommitishValid' => false,
                'isLocalBranch' => false,
                'finder' => AllCheckableFileFinder::class,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider findFilesCallsAllCheckableFileFinderDataProvider
     */
    public function findFilesCallsAllCheckableFileFinder(
        ?string $targetBranchInput,
        bool $isCommitishValid,
        bool $isLocalBranch,
        string $finder,
    ): void {
        $mockedAllowedFileEndings = ['asdqwe'];
        $mockedBlacklistToken = 'qwegfasdfqwe';
        $mockedInclusionlistToken = '12123sdfasdf123123';
        $expectedResult = Mockery::mock(GitChangeSet::class);

        $this->subjectParameters[GitInputValidator::class]->shouldReceive('isCommitishValid')
            ->with($targetBranchInput)->andReturn($isCommitishValid);

        $this->subjectParameters[CommitishComparator::class]->shouldReceive('isLocalBranchEqualTo')
            ->with($targetBranchInput)->andReturn($isLocalBranch);

        $this->subjectParameters[$finder]->shouldReceive('findFiles')
            ->with($mockedAllowedFileEndings, $mockedBlacklistToken, $mockedInclusionlistToken, $targetBranchInput)
            ->andReturn($expectedResult);

        $result = $this->subject->findFiles(
            $mockedAllowedFileEndings,
            $mockedBlacklistToken,
            $mockedInclusionlistToken,
            $targetBranchInput,
        );

        self::assertSame($expectedResult, $result);
    }
}
