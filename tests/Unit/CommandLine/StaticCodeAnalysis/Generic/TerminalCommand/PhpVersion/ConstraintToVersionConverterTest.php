<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use Override;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ConstraintToVersionConverter;

class ConstraintToVersionConverterTest extends TestCase
{
    private ConstraintToVersionConverter $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->subject = new ConstraintToVersionConverter();
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function extractActualPhpVersionWithSemVerReturnsSemVer(): void
    {
        $result = $this->subject->extractActualPhpVersion('1.2.3');

        self::assertSame('1.2.3', $result);
    }


    /**
     * @test
     * @dataProvider extractVersionsDataProvider
     */
    public function extractVersions(string $givenVersion, string $expectedVersion): void
    {
        $result = $this->subject->extractActualPhpVersion($givenVersion);

        self::assertSame($expectedVersion, $result);
    }

    /** @return array<string,array<string,string>> */
    public function extractVersionsDataProvider(): array
    {
        return [
            'version 7.4' => ['givenVersion' => '>=7.4', 'expectedVersion' => '7.4.0',],
            'version >7.4' => ['givenVersion' => '>7.4.33', 'expectedVersion' => '8.0.0',],
            'version 8.0' => ['givenVersion' => '8.0.1', 'expectedVersion' => '8.0.1',],
            'version 8.1' => ['givenVersion' => '^8.1', 'expectedVersion' => '8.1.0',],
            'version 8.2' => ['givenVersion' => '8.2', 'expectedVersion' => '8.2.0',],
            'version 10.23.24' => ['givenVersion' => '10.23.24', 'expectedVersion' => '10.23.24',],
            'version 8.placeholder' => ['givenVersion' => '8.*', 'expectedVersion' => '8.0.0',],
            'version alpha' => ['givenVersion' => '7-BETA', 'expectedVersion' => '7.4.0',],
        ];
    }
}
