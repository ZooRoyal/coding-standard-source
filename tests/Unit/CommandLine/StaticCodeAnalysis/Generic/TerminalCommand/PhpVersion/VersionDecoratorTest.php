<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfoFactory;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecoratorTest extends TestCase
{
    private VersionDecorator $subject;
    private MockInterface|DecorateEvent $mockedEvent;
    private MockInterface|Environment $mockedEnvironment;
    private MockInterface|VersionDependentTerminalCommand $mockedTerminalCommand;
    private MockInterface|OutputInterface $mockedOutput;
    private FileSearchInterface|MockInterface $forgedFileSearch;
    private EnhancedFileInfoFactory $forgedEnhancedFileInfoFactory;

    protected function setUp(): void
    {
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(VersionDependentTerminalCommand::class);
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);
        $this->forgedFileSearch = ContainerFactory::getUnboundContainerInstance()
            ->get(FileSearchInterface::class);
        $this->forgedEnhancedFileInfoFactory = ContainerFactory::getUnboundContainerInstance()
            ->get(EnhancedFileInfoFactory::class);

        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);

        $this->subject = new VersionDecorator(
            $this->mockedEnvironment,
            $this->forgedFileSearch,
            $this->forgedEnhancedFileInfoFactory
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function isInstanceOfTerminalCommandDecorator(): void
    {
        self::assertInstanceOf(TerminalCommandDecorator::class, $this->subject);
    }

    /**
     * @test
     */
    public function skipOnWrongTerminalCommand(): void
    {
        $mockedTerminalCommand = Mockery::mock(TerminalCommand::class);
        $this->mockedEvent->shouldReceive('getTerminalCommand')->once()->andReturn($mockedTerminalCommand);

        $this->mockedTerminalCommand->shouldReceive('setPhpVersion')->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /** @return array<string,array<string,string>> */
    public function setVersionsDataProvider(): array
    {
        return [
            'version 7.4' => ['path' => __DIR__ . '/fixture/versions/7.4', 'expectedVersion' => '7.4.0',],
            'version 8.0' => ['path' => __DIR__ . '/fixture/versions/8.0', 'expectedVersion' => '8.0.1',],
            'version 8.1' => ['path' => __DIR__ . '/fixture/versions/8.1', 'expectedVersion' => '8.1.0',],
            'version 8.2' => ['path' => __DIR__ . '/fixture/versions/8.2', 'expectedVersion' => '8.2.0',],
            'version 8.placeholder' => [
                'path' => __DIR__ . '/fixture/versions/8.placeholder',
                'expectedVersion' => '8.0.0',
            ],
            'version none' => ['path' => __DIR__ . '/fixture/versions/none', 'expectedVersion' => '7.4.0',],
            'config' => ['path' => __DIR__ . '/fixture/places/config', 'expectedVersion' => '8.1.0',],
            'require' => ['path' => __DIR__ . '/fixture/places/require', 'expectedVersion' => '8.1.0',],
            'deepSearch' => ['path' => __DIR__ . '/fixture/deepSearch', 'expectedVersion' => '8.0.3',],
        ];
    }

    /**
     * @test
     * @dataProvider setVersionsDataProvider
     */
    public function setVersions(string $path, string $expectedVersion): void
    {
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $mockedEnhancedFileInfo->expects()->getRealPath()->andReturn($path);
        $mockedEnhancedFileInfo->expects()->getPathname()->andReturn($path);

        $this->mockedEvent->expects()->getTerminalCommand()->twice()->andReturn($this->mockedTerminalCommand);
        $this->mockedEnvironment->expects()->getRootDirectory()->once()->andReturn($mockedEnhancedFileInfo);
        $this->mockedTerminalCommand->expects()->setPhpVersion($expectedVersion)->twice();

        $this->mockedOutput->expects()->writeln(
            '<info>Targeted PHP version is ' . $expectedVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        )->once();

        $this->subject->decorate($this->mockedEvent);
        $this->subject->decorate($this->mockedEvent);
    }
}
