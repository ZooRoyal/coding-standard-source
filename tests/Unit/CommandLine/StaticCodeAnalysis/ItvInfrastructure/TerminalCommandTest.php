<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\ItvInfrastructure;

use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\NoUsefulCommandFoundException;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\ItvInfrastructure\TerminalCommand;

class TerminalCommandTest extends TestCase
{
    private const string FORGED_ABSOLUTE_VENDOR = '/RootDirectory/vendor';
    private const string FORGED_COMMAND_PREFIX = 'npx --prefix /RootDirectory/vendor/.. --no-install '
    . 'validate-microservice-json ';

    private TerminalCommand $subject;
    /** @var Environment|MockInterface */
    private Environment $mockedEnvironment;
    /** @var MockInterface|OutputInterface */
    private OutputInterface $mockedOutput;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEnvironment->shouldReceive('getVendorDirectory->getRealPath')
            ->andReturn(self::FORGED_ABSOLUTE_VENDOR);

        $this->subject = new TerminalCommand($this->mockedEnvironment);
        $this->subject->injectDependenciesAbstractTerminalCommand($this->mockedOutput);
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function terminalCommandValidatesInfrastructureDirectoryIfNoTargetsAreGiven(): void
    {
        $this->mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')
            ->andReturn(__DIR__ . '/fixtures/microserviceProject');
        $this->expectCompilationOutput();

        $result = (string) $this->subject;

        self::assertSame(self::FORGED_COMMAND_PREFIX . 'infrastructure', $result);
    }

    /**
     * @test
     */
    public function terminalCommandDeduplicatesDirectoriesOfTargetedFiles(): void
    {
        $this->expectCompilationOutput();
        $this->subject->addTargets(
            [
                $this->forgeFileInfo('infrastructure/microservice.json'),
                $this->forgeFileInfo('infrastructure/rd-itv-dev.microservice.json'),
                $this->forgeFileInfo('other/service/microservice.json'),
            ],
        );

        $result = (string) $this->subject;

        self::assertSame(self::FORGED_COMMAND_PREFIX . 'infrastructure other/service', $result);
    }

    /**
     * @test
     */
    public function terminalCommandIsSkippedIfProjectHasNoInfrastructureDirectory(): void
    {
        $this->mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')->andReturn(__DIR__);

        $this->expectException(NoUsefulCommandFoundException::class);
        $this->expectExceptionCode(1763287200);
        $this->expectExceptionMessage('There is no infrastructure/microservice.json to validate.');

        $this->subject->__toString();
    }

    /**
     * @test
     */
    public function terminalCommandIsSkippedIfTargetingFoundNoFiles(): void
    {
        $this->subject->addTargets([]);

        $this->expectException(NoUsefulCommandFoundException::class);
        $this->expectExceptionCode(1620831304);

        $this->subject->__toString();
    }

    /**
     * This method builds an EnhancedFileInfo for a path relative to a forged root directory.
     */
    private function forgeFileInfo(string $relativePathname): EnhancedFileInfo
    {
        return new EnhancedFileInfo('/RootDirectory/' . $relativePathname, '/RootDirectory');
    }

    /**
     * This method expects the compilation output written in very verbose mode.
     */
    private function expectCompilationOutput(): void
    {
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with(
                Matchers::startsWith('<info>Compiled TerminalCommand to following string</info>'),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );
    }
}
