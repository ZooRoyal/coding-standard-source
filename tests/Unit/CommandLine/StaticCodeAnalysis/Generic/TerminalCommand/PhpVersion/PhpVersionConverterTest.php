<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;

class PhpVersionConverterTest extends TestCase
{
    private PhpVersionConverter $subject;

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function setUp(): void
    {
        $this->subject = new PhpVersionConverter();
    }

    /** @return array<string,array{0:string,1:int}> */
    public function convertFromSemVerToPhpVersionStringDataProvider(): array
    {
        return [
            '7.2.4' => ['7.2.4', 70204],
            '9.23.23' => ['9.23.23', 92323],
            '7.4' => ['7.4', 70400],
            'just 8' => ['8', 80000],
        ];
    }

    /**
     * @test
     * @dataProvider convertFromSemVerToPhpVersionStringDataProvider
     */
    public function convertFromSemVerToPhpVersionString(string $semVer, int $expectedPhpVersion): void
    {
        $result = $this->subject->convertSemVerToPhpString($semVer);

        self::assertSame($expectedPhpVersion, $result);
    }
}
