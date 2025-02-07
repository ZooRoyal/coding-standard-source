<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\CommandLine\FileSearch;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Timer\Timer;
use SebastianKnott\HamcrestObjectAccessor\HasProperty;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FastCachedFileSearch;

class FastCachedFileSearchTest extends TestCase
{
    private FastCachedFileSearch|MockInterface $subject;
    private EnhancedFileInfoFactory $enhancedFileInfoFactory;
    private EnhancedFileInfo $forgedPath;

    #[Override]
    public function setUp(): void
    {
        $container = ContainerFactory::getUnboundContainerInstance();
        $this->enhancedFileInfoFactory = $container->get(EnhancedFileInfoFactory::class);
        $this->forgedPath = $this->enhancedFileInfoFactory->buildFromPath(__DIR__ . '/../../../..');

        $this->subject = $container->get(FastCachedFileSearch::class);
    }

    /**
     * @test
     */
    public function searchForFiles(): void
    {
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath);

        $resultPaths = array_map(static fn($file) => $file->getRelativePathname(), $result);

        MatcherAssert::assertThat($result, Matchers::everyItem(Matchers::anInstanceOf(EnhancedFileInfo::class)));
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::allOf(
                Matchers::containsInAnyOrder([
                    'src/main/php/Sniffs/PHPCodeSniffer/.dontSniffPHP',
                    'tests/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/Fixtures/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/ReturnType/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/Safe/fixtures/.dontSniffPHP',
                    'tests/System/fixtures/complete/.dontSniffPHP',
                ]),
            ),
        );
    }

    /**
     * @test
     */
    public function searchForFilesIgnoringExcludedDirs(): void
    {
        $exclusions = $this->enhancedFileInfoFactory->buildFromArrayOfPaths(['./tests']);
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, $exclusions);

        $resultPaths = array_map(static fn($file) => $file->getRelativePathname(), $result);

        MatcherAssert::assertThat($result, Matchers::everyItem(Matchers::anInstanceOf(EnhancedFileInfo::class)));
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::allOf(
                Matchers::containsInAnyOrder([
                    'src/main/php/Sniffs/PHPCodeSniffer/.dontSniffPHP',
                ]),
                Matchers::not(
                    Matchers::containsInAnyOrder([
                        'tests/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/Fixtures/.dontSniffPHP',
                        'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/.dontSniffPHP',
                        'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/ReturnType/.dontSniffPHP',
                        'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/Safe/fixtures/.dontSniffPHP',
                        'tests/System/fixtures/complete/.dontSniffPHP',
                    ]),
                ),
            ),
        );
    }

    /**
     * @test
     */
    public function searchForFilesIgnoringBelowMinDepth(): void
    {
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, minDepth: 5);

        $resultPaths = array_map(static fn($file) => $file->getRelativePathname(), $result);

        MatcherAssert::assertThat($result, Matchers::everyItem(Matchers::anInstanceOf(EnhancedFileInfo::class)));
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::not(
                Matchers::containsInAnyOrder([
                    'tests/System/fixtures/complete/.dontSniffPHP',
                ]),
            ),
        );
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::containsInAnyOrder(
                'tests/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/Fixtures/.dontSniffPHP',
                'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/.dontSniffPHP',
                'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/ReturnType/.dontSniffPHP',
                'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/Safe/fixtures/.dontSniffPHP',
                'src/main/php/Sniffs/PHPCodeSniffer/.dontSniffPHP',
            ),
        );
    }

    /**
     * @test
     */
    public function searchForFilesUsesCache(): void
    {
        $timer1 = new Timer();
        $timer2 = new Timer();

        $timer1->start();
        $result1 = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, minDepth: 4);
        $duration1 = $timer1->stop();
        $timer2->start();
        $result2 = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, minDepth: 4);
        $duration2 = $timer2->stop();

        self::assertLessThan($duration1->asNanoseconds(), $duration2->asNanoseconds());
        self::assertSame($result1, $result2);
    }

    /**
     * @test
     */
    public function searchForFilesExcludedByPreviousSearch(): void
    {
        $exclusions = $this->enhancedFileInfoFactory->buildFromArrayOfPaths(['./tests']);

        $this->subject->listFolderFiles('composer.json', $this->forgedPath, $exclusions, minDepth: 4);
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, minDepth: 4);

        MatcherAssert::assertThat(
            $result,
            Matchers::hasValue(
                HasProperty::hasProperty('relativePathname', 'tests/System/fixtures/complete/.dontSniffPHP'),
            ),
        );
    }

    /**
     * @test
     */
    public function searchStopsAtMaxDepth(): void
    {
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $this->forgedPath, maxDepth: 5);

        $resultPaths = array_map(static fn($file) => $file->getRelativePathname(), $result);

        MatcherAssert::assertThat($result, Matchers::everyItem(Matchers::anInstanceOf(EnhancedFileInfo::class)));
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::containsInAnyOrder([
                'tests/System/fixtures/complete/.dontSniffPHP',
            ]),
        );
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::not(
                Matchers::containsInAnyOrder(
                    'tests/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/Fixtures/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/ReturnType/.dontSniffPHP',
                    'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/Safe/fixtures/.dontSniffPHP',
                    'src/main/php/Sniffs/PHPCodeSniffer/.dontSniffPHP',
                ),
            ),
        );
    }
}
