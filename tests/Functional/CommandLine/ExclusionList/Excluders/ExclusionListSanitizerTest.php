<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\CommandLine\ExclusionList\Excluders;

use Override;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\ExclusionListSanitizer;

class ExclusionListSanitizerTest extends TestCase
{
    private ExclusionListSanitizer $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->subject = new ExclusionListSanitizer();
    }

    /**
     * @test
     */
    public function sanitizeExclusionList(): void
    {
        $expectedResult = $this->prepareMockedEnhancedFileInfo([
            '0' => __DIR__ . '/../../../fixtures/asd',
            '1' => __DIR__ . '/../../../fixtures/asdqwe',
            '2' => __DIR__ . '/../../../fixtures/yxc/asd',
        ]);
        $input = [
            $expectedResult[0],
            ...$this->prepareMockedEnhancedFileInfo([
                __DIR__ . '/../../../fixtures/asd',
                __DIR__ . '/../../../fixtures/asd/asdqwe',
            ]),
            $expectedResult[1],
            $expectedResult[2],
        ];

        $result = $this->subject->sanitizeExclusionList($input);

        self::assertSame($expectedResult, $result);
    }

    /**
     * Converts file paths to enhancedFileInfos
     *
     * @param array<string> $filePaths
     *
     * @return array<EnhancedFileInfo>
     */
    private function prepareMockedEnhancedFileInfo(array $filePaths): array
    {
        $enhancedFileMocks = [];
        foreach ($filePaths as $filePath) {
            $enhancedFileMocks[] = new EnhancedFileInfo($filePath, '/');
        }
        return $enhancedFileMocks;
    }
}
