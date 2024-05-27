<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\PHPStan;

use Hamcrest\Matcher;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\ExclusionListFactory;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\PhpVersionConverter;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan\PHPStanConfigGenerator;

class PHPStanConfigGeneratorTest extends TestCase
{
    private PHPStanConfigGenerator $subject;
    private MockInterface&Filesystem $mockedFilesystem;
    private MockInterface&PhpVersionConverter $mockedPhpVersionConverter;
    private MockInterface&OutputInterface $mockedOutput;
    private MockInterface&ComposerInterpreter $mockedComposerInterpreter;
    private MockInterface&ExclusionListFactory $mockedExclusionListFactory;
    private string $mockedPackageDirectory = '/tmp/phpunitTest';
    private string $mockedRootDirectory = '/tmp';
    private string $mockedVendorDirectory = '/tmp/vendor';
    /** @var array<string|string> */
    private array $forgedExcludedFilePaths = ['a', 'b'];

    protected function setUp(): void
    {
        $this->mockedFilesystem = Mockery::mock(Filesystem::class);
        $mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedPhpVersionConverter = Mockery::mock(PhpVersionConverter::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);
        $this->mockedComposerInterpreter = Mockery::mock(ComposerInterpreter::class);
        $this->mockedExclusionListFactory = Mockery::mock(ExclusionListFactory::class);

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
            $this->mockedPhpVersionConverter,
            $this->mockedOutput,
            $this->mockedComposerInterpreter,
            $this->mockedExclusionListFactory,
        );
    }

    protected function assertPostConditions(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function addDynamicConfigValuesModifiesArray(): void
    {
        $this->prepareMockedFilesystem();
        $forgedBaseConfig = ['g' => 'h'];

        $this->mockedOutput->shouldReceive('writeln')->once()->with(
            '<info>deployer/deployer not found. Skip loading /src/functions.php.</info>',
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $forgedExcludedFiles = [];
        foreach ($this->forgedExcludedFilePaths as $forgedExcludedFilePath) {
            $mockedEnhancedFileInfo = mock(EnhancedFileInfo::class);
            $mockedEnhancedFileInfo->shouldReceive('getRealPath')->atLeast()->once()
                ->andReturn($forgedExcludedFilePath);
            $forgedExcludedFiles[] = $mockedEnhancedFileInfo;
        }

        $this->mockedExclusionListFactory->shouldReceive('build')->once()->andReturn($forgedExcludedFiles);
        $this->mockedComposerInterpreter->shouldReceive('getMinimalViablePhpVersion')->once()->andReturn('8.1.2');
        $this->mockedPhpVersionConverter->shouldReceive('convertSemVerToPhpString')->once()->andReturn(80102);

        $result = $this->subject->addDynamicConfigValues($forgedBaseConfig);

        MatcherAssert::assertThat($result, $this->buildConfigMatcher($forgedBaseConfig));
    }


    public function buildFunctionsMatcher(): Matcher
    {
        $functionsMatcher = H::hasKeyValuePair(
            'scanFiles',
            H::hasItems(
                $this->mockedVendorDirectory . '/hamcrest/hamcrest-php/hamcrest/Hamcrest.php',
                $this->mockedVendorDirectory . '/sebastianknott/hamcrest-object-accessor/src/functions.php',
                $this->mockedVendorDirectory . '/mockery/mockery/library/helpers.php',
            ),
        );
        return $functionsMatcher;
    }


    public function buildExcludesMatcher(): Matcher
    {
        $excludesMatcher = H::hasKeyValuePair(
            'excludePaths',
            H::hasKeyValuePair(
                'analyseAndScan',
                H::arrayContainingInAnyOrder(...$this->forgedExcludedFilePaths)
            )
        );
        return $excludesMatcher;
    }

    public function buildStaticDirectoriesMatcher(): Matcher
    {
        return H::hasKeyValuePair(
            'scanDirectories',
            H::allOf(
                H::hasItem($this->mockedRootDirectory . '/Plugins'),
                H::hasItem($this->mockedRootDirectory . '/custom/project'),
            ),
        );
    }

    public function buildVersionMatcher(): Matcher
    {
        $versionMatcher = H::hasKeyValuePair('phpVersion', H::identicalTo(80102));
        return $versionMatcher;
    }

    public function buildTmpDirMatcher(): Matcher
    {
        $quotedTempDir = preg_quote(sys_get_temp_dir(), '/');
        $tmpDirMatcher = H::hasKeyValuePair(
            'tmpDir',
            H::matchesPattern('/' . $quotedTempDir . '\/phpstan\d+/')
        );
        return $tmpDirMatcher;
    }

    /**
     * Build matcher from given array
     *
     * @param array<string,array<int|string,array<int,string>|string>|string> $contains
     */
    public function buildContainsMatcher(array $contains): Matcher
    {
        $containsMatcherParts = [];
        foreach ($contains as $key => $value) {
            $containsMatcherParts[] = H::hasKeyValuePair($key, $value);
        }
        $containsMatcher = H::allOf(...$containsMatcherParts);
        return $containsMatcher;
    }

    public function buildParametersMatcher(): Matcher
    {
        $functionsMatcher = $this->buildFunctionsMatcher();
        $excludesMatcher = $this->buildExcludesMatcher();
        $staticDirectoriesMatcher = $this->buildStaticDirectoriesMatcher();
        $versionMatcher = $this->buildVersionMatcher();
        $tmpDirMatcher = $this->buildTmpDirMatcher();

        $parametersMatcher = H::hasKeyValuePair(
            'parameters',
            H::allOf($functionsMatcher, $excludesMatcher, $staticDirectoriesMatcher, $versionMatcher, $tmpDirMatcher)
        );

        return $parametersMatcher;
    }

    /**
     * This method builds the validation matcher for the configuration.
     *
     * @param array<string,array<int|string,array<int,string>|string>|string> $contains
     */
    private function buildConfigMatcher(array $contains): Matcher
    {
        $containsMatcher = $this->buildContainsMatcher($contains);
        $parametersMatcher = $this->buildParametersMatcher();

        $configMatcher = H::allOf($parametersMatcher, $containsMatcher);

        return $configMatcher;
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
                ),
            )->andReturn(false);

        $this->mockedFilesystem->shouldReceive('exists')->times(6)
            ->with(
                H::anyOf(
                    $this->mockedRootDirectory . '/Plugins',
                    $this->mockedRootDirectory . '/custom/project',
                    $this->mockedVendorDirectory,
                    $this->mockedVendorDirectory . '/hamcrest/hamcrest-php',
                    $this->mockedVendorDirectory . '/sebastianknott/hamcrest-object-accessor',
                    $this->mockedVendorDirectory . '/mockery/mockery',
                ),
            )->andReturn(true);
    }
}
