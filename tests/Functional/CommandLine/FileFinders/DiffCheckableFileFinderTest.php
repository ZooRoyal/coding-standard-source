<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\CommandLine\FileFinders;

use DI\Container;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Mockery;
use Override;
use PHPUnit\Framework\TestCase;
use SebastianKnott\HamcrestObjectAccessor\HasProperty;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\FileFinder\DiffCheckableFileFinder;
use Zooroyal\CodingStandard\CommandLine\FileFinder\GitChangeSet;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;

class DiffCheckableFileFinderTest extends TestCase
{
    /** @var array<string> */
    private array $forgedFileSet;
    private string $forgedRawDiffUnfilteredString;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->forgedFileSet = [
            'topFolder/allowedChangesFile',
            'topFolder/folder/subFolder/allowedChangesFile',
            'topFolder/folder/subFolder/.doChangeFiles',
        ];
        $this->forgedRawDiffUnfilteredString = 'topFolder/allowedChangesFile' . PHP_EOL
            . 'topFolder/folder/subFolder/allowedChangesFile' . PHP_EOL
            . 'topFolder/folder/subFolder/.doChangeFiles' . PHP_EOL
            . 'topFolder/folder/subFolder/finalFolder/disallowedChangesFile' . PHP_EOL
            . 'topFolder/folder/subFolder/finalFolder/.dontChangeFiles' . PHP_EOL
            . 'topFolder/folder/disallowedChangesFile' . PHP_EOL
            . 'topFolder/folder/.dontChangeFiles';
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function findFiles(): void
    {
        $forgedRootDirectory = __DIR__ . '/fixtures';

        $container = $this->setUpMockedObjects($forgedRootDirectory, 'myTarget', $this->forgedRawDiffUnfilteredString);
        /** @var DiffCheckableFileFinder $subject */
        $subject = $container->get(DiffCheckableFileFinder::class);

        $result = $subject->findFiles([], '.dontChangeFiles', '.doChangeFiles', 'myTarget');

        MatcherAssert::assertThat(
            $result,
            H::both(H::anInstanceOf(GitChangeSet::class))
                ->andAlso(
                    HasProperty::hasProperty(
                        'files',
                        H::arrayContainingInAnyOrder(
                            [
                                HasProperty::hasProperty(
                                    'getRealPath',
                                    $forgedRootDirectory . '/' . $this->forgedFileSet[0],
                                ),
                                HasProperty::hasProperty(
                                    'getRealPath',
                                    $forgedRootDirectory . '/' . $this->forgedFileSet[1],
                                ),
                                HasProperty::hasProperty(
                                    'getRealPath',
                                    $forgedRootDirectory . '/' . $this->forgedFileSet[2],
                                ),
                            ],
                        ),
                    ),
                ),
        );
    }

    /**
     * Setup all mocked objects for test isolation.
     */
    private function setUpMockedObjects(
        string $forgedRootDirectory,
        string $targetBranch,
        string $forgedRawDiffUnfilteredString,
    ): Container {
        $targetMergeBase = '123asdasdMergeBase123123asd';

        $mockedProcessRunner = Mockery::mock(ProcessRunner::class)->makePartial();
        $mockedProcessRunner->shouldReceive('runAsProcess')
            ->with('git', 'rev-parse', '--show-toplevel')->andReturn($forgedRootDirectory);
        $mockedProcessRunner->shouldReceive('runAsProcess')->once()
            ->with('git', 'merge-base', 'HEAD', $targetBranch)->andReturn($targetMergeBase);
        $mockedProcessRunner->shouldReceive('runAsProcess')->once()
            ->with('git', 'diff', '--name-only', '--diff-filter=d', $targetMergeBase)
            ->andReturn($forgedRawDiffUnfilteredString);

        $container = ContainerFactory::getUnboundContainerInstance();
        $container->set(ProcessRunner::class, $mockedProcessRunner);
        return $container;
    }
}
