<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\PHPStan;

use Hamcrest\Matcher;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Mockery;
use Mockery\MockInterface;
use Nette\Neon\Neon;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan\PHPStanConfigGenerator;

class PHPStanConfigGeneratorTest extends TestCase
{
    private PHPStanConfigGenerator $subject;
    private MockInterface|Filesystem $mockedFilesystem;
    private MockInterface|PhpVersionConverter $mockedPhpVersionConverter;
    private MockInterface|OutputInterface $mockedOutput;
    private string $mockedPackageDirectory = '/tmp/phpunitTest';
    private string $mockedRootDirectory = '/tmp';
    private string $mockedVendorDirectory = '/tmp/vendor';
    private string $forgedExcludedFilePath = '/asdqweqwe/ww';

    protected function setUp(): void
    {
        $this->mockedPhpVersionConverter = Mockery::mock(PhpVersionConverter::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedFilesystem = Mockery::mock(Filesystem::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $mockedEnvironment
            ->shouldReceive('getPackageDirectory->getRealPath')
            ->andReturn($this->mockedPackageDirectory);
        $mockedEnvironment
            ->shouldReceive('getRootDirectory->getRealPath')
            ->andReturn($this->mockedRootDirectory);
        $mockedEnvironment
            ->shouldReceive('getVendorDirectory->getRealPath')
            ->andReturn($this->mockedVendorDirectory);

        $this->subject = new PHPStanConfigGenerator(
            $this->mockedFilesystem,
            $mockedEnvironment,
            $this->mockedPhpVersionConverter
        );
    }

    protected function assertPostConditions(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function getConfigPathReturnsConfigPath(): void
    {
        $result = $this->subject->getConfigPath();

        self::assertSame($this->mockedPackageDirectory . '/config/phpstan/phpstan.neon', $result);
    }

    /**
     * @test
     */
    public function writeConfigFileWritesConfigFileToFilesystem(): void
    {
        $mockedEnhancedFileInfo = Mockery::mock(EnhancedFileInfo::class);
        $mockedExclusionList = [$mockedEnhancedFileInfo];
        $forgedPhpVersion = '8.1.2';
        $expectedPhpVersionString = 80102;

        $this->prepareMockedFilesystem();

        $mockedEnhancedFileInfo->shouldReceive('getRealPath')->atLeast()->once()
            ->andReturn($this->forgedExcludedFilePath);

        $this->mockedOutput->shouldReceive('writeln')->times(2)->with(
            H::anyOf(
                '<info>Writing new PHPStan configuration.</info>' . PHP_EOL,
                '<info>deployer/deployer not found. Skip loading /src/functions.php.</info>',
            ),
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $this->mockedPhpVersionConverter->shouldReceive('convertSemVerToPhpString')->once()
            ->with($forgedPhpVersion)->andReturn($expectedPhpVersionString);

        $this->subject->writeConfigFile($this->mockedOutput, $mockedExclusionList, $forgedPhpVersion);
    }

    /**
     * This method builds the validation matcher for the configuration.
     */
    private function buildConfigMatcher(): Matcher
    {
        $includesMatcher = H::hasKeyValuePair(
            'includes',
            H::hasItem($this->mockedPackageDirectory . '/config/phpstan/phpstan.neon' . '.dist'),
        );

        $functionsMatcher = H::hasKeyValuePair(
            'scanFiles',
            H::hasItems(
                $this->mockedVendorDirectory . '/hamcrest/hamcrest-php/hamcrest/Hamcrest.php',
                $this->mockedVendorDirectory . '/sebastianknott/hamcrest-object-accessor/src/functions.php',
                $this->mockedVendorDirectory . '/mockery/mockery/library/helpers.php'
            ),
        );

        $excludesMatcher = H::hasKeyValuePair('excludePaths', H::hasItem($this->forgedExcludedFilePath));
        $staticDirectoriesMatcher = H::hasKeyValuePair(
            'scanDirectories',
            H::allOf(
                H::hasItem($this->mockedRootDirectory . '/Plugins'),
                H::hasItem($this->mockedRootDirectory . '/custom/project'),
            ),
        );
        $versionMatcher = H::hasKeyValuePair('phpVersion', H::identicalTo(80102));

        $parametersMatcher = H::hasKeyValuePair(
            'parameters',
            H::allOf($functionsMatcher, $excludesMatcher, $staticDirectoriesMatcher, $versionMatcher),
        );

        $matcher = H::allOf(
            $includesMatcher,
            $parametersMatcher,
        );

        return $matcher;
    }

    /**
     * Add expectations to filesystem regarding existence of static directories and writing file to disc.
     * One file directory will not be found.
     */
    private function prepareMockedFilesystem(): void
    {
        $this->mockedFilesystem->shouldReceive('exists')->times(3)
            ->with(
                H::anyOf(
                    $this->mockedRootDirectory . '/custom/plugins',
                    $this->mockedVendorDirectory . '/deployer/deployer',
                    $this->mockedVendorDirectory . '-bin',
                )
            )->andReturn(false);

        $this->mockedFilesystem->shouldReceive('exists')->times(6)
            ->with(
                H::anyOf(
                    $this->mockedRootDirectory . '/Plugins',
                    $this->mockedRootDirectory . '/custom/project',
                    $this->mockedVendorDirectory,
                    $this->mockedVendorDirectory . '/hamcrest/hamcrest-php',
                    $this->mockedVendorDirectory . '/sebastianknott/hamcrest-object-accessor',
                    $this->mockedVendorDirectory . '/mockery/mockery'
                )
            )->andReturn(true);

        $this->mockedFilesystem->shouldReceive('dumpFile')->once()
            ->with(
                $this->mockedPackageDirectory . '/config/phpstan/phpstan.neon',
                Mockery::on(function ($parameter) {
                    $configuration = Neon::decode($parameter);
                    MatcherAssert::assertThat($configuration, $this->buildConfigMatcher());
                    return true;
                })
            );
    }
}
