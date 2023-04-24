<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\CommandLine\ExclusionList\Excluders;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Timer\Timer;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\FastCachedFileSearch;

class FastCachedFileSearchTest extends TestCase
{
    private FastCachedFileSearch|MockInterface $subject;
    private EnhancedFileInfoFactory $enhancedFileInfoFactory;

    public function setUp(): void
    {
        $container = ContainerFactory::getUnboundContainerInstance();
        $this->enhancedFileInfoFactory = $container->get(EnhancedFileInfoFactory::class);

        $this->subject = $container->get(FastCachedFileSearch::class);
    }

    /**
     * @test
     */
    public function searchForFiles(): void
    {
        $path = $this->enhancedFileInfoFactory->buildFromPath(__DIR__ . '/../../../../..');
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $path);

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
                    'tests/System/fixtures/complete/.dontSniffPHP',
                ])
            )
        );
    }

    /**
     * @test
     */
    public function searchForFilesIgnoringExcludedDirs(): void
    {
        $path = $this->enhancedFileInfoFactory->buildFromPath(__DIR__ . '/../../../../..');
        $exclusions = $this->enhancedFileInfoFactory->buildFromArrayOfPaths(['./tests']);
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $path, $exclusions);

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
                        'tests/System/fixtures/complete/.dontSniffPHP',
                    ])
                )
            )
        );
    }

    /**
     * @test
     */
    public function searchForFilesIgnoringBelowMinDepth(): void
    {
        $path = $this->enhancedFileInfoFactory->buildFromPath(__DIR__ . '/../../../../..');
        $result = $this->subject->listFolderFiles('.dontSniffPHP', $path, minDepth: 5);

        $resultPaths = array_map(static fn($file) => $file->getRelativePathname(), $result);

        MatcherAssert::assertThat($result, Matchers::everyItem(Matchers::anInstanceOf(EnhancedFileInfo::class)));
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::not(
                Matchers::containsInAnyOrder([
                    'tests/System/fixtures/complete/.dontSniffPHP',
                ])
            ),
        );
        MatcherAssert::assertThat(
            $resultPaths,
            Matchers::containsInAnyOrder(
                'tests/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/Fixtures/.dontSniffPHP',
                'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/.dontSniffPHP',
                'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/ReturnType/.dontSniffPHP',
                'src/main/php/Sniffs/PHPCodeSniffer/.dontSniffPHP',
            )
        );
    }

    /**
     * @test
     */
    public function searchForFilesUsesCache(): void
    {
        $path = $this->enhancedFileInfoFactory->buildFromPath(__DIR__ . '/../../../../..');

        $timer1 = new Timer();
        $timer2 = new Timer();

        $timer1->start();
        $result1 = $this->subject->listFolderFiles('.dontSniffPHP', $path, minDepth: 5);
        $duration1 = $timer1->stop();
        $timer2->start();
        $result2 = $this->subject->listFolderFiles('.dontSniffPHP', $path, minDepth: 5);
        $duration2 = $timer2->stop();

        self::assertLessThan($duration1->asNanoseconds() / 3 * 2, $duration2->asNanoseconds());
        self::assertSame($result1, $result2);
    }
}
