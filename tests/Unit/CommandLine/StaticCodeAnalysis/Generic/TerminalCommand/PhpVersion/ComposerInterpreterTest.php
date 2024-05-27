<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use JsonException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ConstraintToVersionConverter;

use function Safe\preg_match;

class ComposerInterpreterTest extends TestCase
{
    private MockInterface&Environment $mockedEnvironment;
    private MockInterface&ConstraintToVersionConverter $mockedConstraintToVersionConverter;

    private ComposerInterpreter $subject;
    private FileSearchInterface $mockedFileSearchInterface;

    protected function setUp(): void
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedConstraintToVersionConverter = Mockery::mock(ConstraintToVersionConverter::class);
        $this->mockedFileSearchInterface = ContainerFactory::getUnboundContainerInstance()
            ->get(FileSearchInterface::class);

        $this->subject = new ComposerInterpreter(
            $this->mockedEnvironment,
            $this->mockedConstraintToVersionConverter,
            $this->mockedFileSearchInterface,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider getMinimalRootPackagePhpVersionReturnsPhpVersionDataProvider
     */
    public function getMinimalRootPackagePhpVersionReturnsPhpVersion(string $path, string $expectedVersion): void
    {
        $expectedExtractedVersion = $expectedVersion . '.1';

        $this->mockedEnvironment->allows('getRootDirectory->getRealPath')->andReturn(__DIR__ . $path);

        $this->mockedConstraintToVersionConverter->allows()->extractActualPhpVersion($expectedVersion)
            ->andReturn($expectedExtractedVersion);

        $result = $this->subject->getMinimalRootPackagePhpVersion();
        self::assertSame($expectedExtractedVersion, $result);
    }

    /**
     * @test
     */
    public function getLocalPhpVersionConstraintThrowsErrorOnCorruptJson(): void
    {
        $this->expectException(JsonException::class);

        $path = '/fixture/places/corrupt';
        $this->mockedEnvironment->allows('getRootDirectory->getRealPath')->andReturn(__DIR__ . $path);

        $this->subject->getMinimalRootPackagePhpVersion();
    }

    /** @return array<string,array<string,string>> */
    public function getMinimalRootPackagePhpVersionReturnsPhpVersionDataProvider(): array
    {
        return [
            'require' => ['path' => '/fixture/places/require', 'expectedVersion' => '8.2'],
            'config' => ['path' => '/fixture/places/config', 'expectedVersion' => '8.1'],
            'none' => ['path' => '/fixture/places/none', 'expectedVersion' => '*'],
        ];
    }

    /**
     * @test
     * @dataProvider getMinimalViablePhpVersionDataProvider
     */
    public function getMinimalViablePhpVersionReturnsVersion(string $path, string $expectedVersion): void
    {
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $mockedEnhancedFileInfo->allows()->getRealPath()->andReturn($path);
        $mockedEnhancedFileInfo->allows()->getPathname()->andReturn($path);

        $this->mockedEnvironment->allows()->getRootDirectory()->andReturn($mockedEnhancedFileInfo);

        $this->mockedConstraintToVersionConverter->expects()->extractActualPhpVersion(self::anything())
            ->atLeast()->times(1)->andReturnUsing(static function (string $parameter) {
                preg_match('/(\d+\.\d+)\.?\d*/', $parameter, $matches);
                return $matches[1] . '.1';
            });

        $this->subject->getMinimalViablePhpVersion();
        $result = $this->subject->getMinimalViablePhpVersion();
        self::assertSame($expectedVersion, $result);
    }

    /** @return array<string,array<string,string>> */
    public function getMinimalViablePhpVersionDataProvider(): array
    {
        return [
            'require' => [
                'path' => __DIR__ . '/fixture/places/require',
                'expectedVersion' => '8.2.1',
            ],
            'deepSearch' => [
                'path' => __DIR__ . '/fixture/deepSearch',
                'expectedVersion' => '8.0.1',
            ],
        ];
    }
}
