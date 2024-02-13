<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use JsonException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ConstraintToVersionConverter;

class ComposerInterpreterTest extends TestCase
{
    private MockInterface&Environment $mockedEnvironment;
    private MockInterface&EnhancedFileInfoFactory $mockedEnhancedFileInfoFactory;
    private MockInterface&ConstraintToVersionConverter $mockedConstraintToVersionConverter;

    private ComposerInterpreter $subject;

    protected function setUp(): void
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedEnhancedFileInfoFactory = Mockery::mock(EnhancedFileInfoFactory::class);
        $this->mockedConstraintToVersionConverter = Mockery::mock(ConstraintToVersionConverter::class);

        $this->subject = new ComposerInterpreter(
            $this->mockedEnvironment,
            $this->mockedEnhancedFileInfoFactory,
            $this->mockedConstraintToVersionConverter
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider getLocalPhpVersionConstraintReturnsPhpVersionConstraintDataProvider
     */
    public function getLocalPhpVersionConstraintReturnsPhpVersionConstraint(string $path, string $expectedVersion): void
    {
        $expectedExtractedVersion = $expectedVersion . '.1';

        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $this->mockedEnvironment->allows('getRootDirectory->getRealPath')->andReturn(__DIR__ . $path);
        $this->mockedEnhancedFileInfoFactory->allows()->buildFromPath(__DIR__ . $path . '/composer.json')
            ->andReturn($mockedEnhancedFileInfo);
        $mockedEnhancedFileInfo->allows()->getRealPath()->andReturn(__DIR__ . $path . '/composer.json');

        $this->mockedConstraintToVersionConverter->allows()->extractActualPhpVersion($expectedVersion)
            ->andReturn($expectedExtractedVersion);

        $result = $this->subject->getLocalPhpVersionConstraint();
        self::assertSame($expectedExtractedVersion, $result);
    }

    /**
     * @test
     */
    public function getLocalPhpVersionConstraintThrowsErrorOnCorruptJson(): void
    {
        $this->expectException(JsonException::class);
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $path = '/fixture/places/corrupt';
        $this->mockedEnvironment->allows('getRootDirectory->getRealPath')->andReturn(__DIR__ . $path);
        $this->mockedEnhancedFileInfoFactory->allows()->buildFromPath(__DIR__ . $path . '/composer.json')
            ->andReturn($mockedEnhancedFileInfo);
        $mockedEnhancedFileInfo->allows()->getRealPath()->andReturn(__DIR__ . $path . '/composer.json');
        $this->subject->getLocalPhpVersionConstraint();
    }

    /** @return array<string,array<string,string>> */
    public function getLocalPhpVersionConstraintReturnsPhpVersionConstraintDataProvider(): array
    {
        return [
            'require' => ['path' => '/fixture/places/require', 'expectedVersion' => '8.2'],
            'config' => ['path' => '/fixture/places/config', 'expectedVersion' => '8.1'],
            'none' => ['path' => '/fixture/places/none', 'expectedVersion' => '*'],
        ];
    }
}
