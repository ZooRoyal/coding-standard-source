<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ConstraintToVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDecorator;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\VersionDependentTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecoratorTest extends TestCase
{
    private MockInterface&Environment $mockedEnvironment;
    private FileSearchInterface $forgedFileSearch;
    private MockInterface&ConstraintToVersionConverter $mockedConstraintToVersionConverter;
    private MockInterface&ComposerInterpreter $mockedComposerInterpreter;
    private MockInterface&DecorateEvent $mockedEvent;
    private MockInterface&VersionDependentTerminalCommand $mockedTerminalCommand;
    private MockInterface&OutputInterface $mockedOutput;
    private VersionDecorator $subject;

    protected function setUp(): void
    {
        $this->mockedEvent = Mockery::mock(DecorateEvent::class);
        $this->mockedTerminalCommand = Mockery::mock(VersionDependentTerminalCommand::class);
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);
        $this->forgedFileSearch = ContainerFactory::getUnboundContainerInstance()
            ->get(FileSearchInterface::class);
        $this->mockedConstraintToVersionConverter = Mockery::mock(ConstraintToVersionConverter::class);
        $this->mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);

        $this->mockedEvent->shouldReceive('getOutput')->andReturn($this->mockedOutput);

        $this->subject = new VersionDecorator(
            $this->mockedEnvironment,
            $this->forgedFileSearch,
            $this->mockedConstraintToVersionConverter,
            $this->mockedComposerInterpreter,
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
        $this->mockedEvent->expects()->getTerminalCommand()->andReturns($mockedTerminalCommand);
        $this->mockedTerminalCommand->allows()->setPhpVersion(self::anything())->never();

        $this->subject->decorate($this->mockedEvent);
    }

    /** @return array<string,array<string,string>> */
    public function setVersionsDataProvider(): array
    {
        return [
            'require' => ['path' => __DIR__ . '/fixture/places/require', 'expectedVersion' => '7.4', 'deep' => false,],
            'deepSearch' => ['path' => __DIR__ . '/fixture/deepSearch', 'expectedVersion' => '8.0.1', 'deep' => true],
        ];
    }

    /**
     * @test
     * @dataProvider setVersionsDataProvider
     */
    public function setVersions(string $path, string $expectedVersion, bool $deep): void
    {
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);

        $mockedEnhancedFileInfo->allows()->getRealPath()->andReturn($path);
        $mockedEnhancedFileInfo->allows()->getPathname()->andReturn($path);

        $this->mockedEvent->expects()->getTerminalCommand()->twice()->andReturn($this->mockedTerminalCommand);
        $this->mockedEnvironment->expects()->getRootDirectory()->once()->andReturn($mockedEnhancedFileInfo);

        $this->mockedComposerInterpreter->allows()->getLocalPhpVersionConstraint()->once()->andReturn('7.4');
        if ($deep) {
            $this->mockedConstraintToVersionConverter->expects()->extractActualPhpVersion(self::anything())
                ->twice()->andReturnUsing(static fn(string $parameter) => $parameter . '.1');
        }

        $this->mockedTerminalCommand->expects()->setPhpVersion($expectedVersion)->twice();

        $this->mockedOutput->expects()->writeln(
            '<info>Targeted PHP version is ' . $expectedVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        )->twice();

        $this->subject->decorate($this->mockedEvent);
        $this->subject->decorate($this->mockedEvent);
    }
}
