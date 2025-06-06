<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\PHPCopyPasteDetector;

use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPCopyPasteDetector\TerminalCommand;
use Zooroyal\CodingStandard\Tests\Tools\TerminalCommandTestData;

class TerminalCommandTest extends TestCase
{
    private const string FORGED_PACKAGE_DIRECTORY = '/packageDirectory';
    private const string FORGED_RELATIV_ROOT = '.';
    private const string FORGED_ABSOLUTE_ROOT = '/RootDirectory';
    private const string FORGED_ABSOLUTE_VENDOR = '/vendor';

    private TerminalCommand $subject;
    /** @var MockInterface|\Zooroyal\CodingStandard\CommandLine\Environment\Environment */
    private Environment $mockedEnvironment;
    /** @var MockInterface|OutputInterface */
    private OutputInterface $mockedOutput;
    /** @var MockInterface|ProcessRunner */
    private ProcessRunner $mockedProcessRunner;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedProcessRunner = Mockery::mock(ProcessRunner::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEnvironment->shouldReceive('getPackageDirectory->getRealPath')
            ->andReturn(self::FORGED_PACKAGE_DIRECTORY);
        $this->mockedEnvironment->shouldReceive('getRootDirectory->getRelativePathname')
            ->andReturn(self::FORGED_RELATIV_ROOT);
        $this->mockedEnvironment->shouldReceive('getRootDirectory->getRealPath')
            ->andReturn(self::FORGED_ABSOLUTE_ROOT);
        $this->mockedEnvironment->shouldReceive('getVendorDirectory->getRealPath')
            ->andReturn(self::FORGED_ABSOLUTE_VENDOR);

        $this->subject = new TerminalCommand($this->mockedEnvironment, $this->mockedProcessRunner);
        $this->subject->injectDependenciesAbstractTerminalCommand($this->mockedOutput);
    }

    #[Override]
    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider terminalCommandCompilationDataProvider
     */
    public function terminalCommandCompilation(TerminalCommandTestData $data): void
    {
        $this->mockedOutput->shouldReceive('writeln')->once()
            ->with(
                Matchers::startsWith(
                    '<info>Compiled TerminalCommand to following string</info>' . PHP_EOL . $data->getExpectedCommand(),
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );

        $this->mockedProcessRunner->shouldReceive('runAsProcess')
            ->with(
                'find',
                self::FORGED_ABSOLUTE_ROOT,
                '-path',
                '*/custom/plugins/*',
                '-name',
                'Installer.php',
                '-maxdepth',
                '4',
            )
            ->andReturn(
                self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blabla/Installer.php' . PHP_EOL
                . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blubblub/Installer.php' . PHP_EOL,
            );

        $this->subject->addAllowedFileExtensions($data->getExtensions());
        $this->subject->addExclusions($data->getExcluded());
        if ($data->getTargets() !== null) {
            $this->subject->addTargets($data->getTargets());
        }

        $result = (string) $this->subject;
        $resultingArray = $this->subject->toArray();

        self::assertSame($data->getExpectedCommand(), $result);
        self::assertSame($result, implode(' ', $resultingArray));
    }

    /**
     * This data provider needs to be long because it contains all testing data.
     *
     * @return array<string,array<int,TerminalCommandTestData>>
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength
    public function terminalCommandCompilationDataProvider(): array
    {
        return [
            'all' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'php ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/bin/phpcpd --fuzzy --suffix qweasd --suffix argh c d',
                        'excluded' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_VENDOR . '/a', self::FORGED_ABSOLUTE_VENDOR),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/b', self::FORGED_ABSOLUTE_ROOT),
                        ],
                        'extensions' => ['qweasd', 'argh'],
                        'targets' => [
                            new EnhancedFileInfo(
                                self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blabla/Installer.php',
                                self::FORGED_ABSOLUTE_ROOT,
                            ),
                            new EnhancedFileInfo(
                                self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blubblub/Installer.php',
                                self::FORGED_ABSOLUTE_ROOT,
                            ),
                            new EnhancedFileInfo(
                                self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/ZRBannerSlider/ZRBannerSlider.php',
                                self::FORGED_ABSOLUTE_ROOT,
                            ),
                            new EnhancedFileInfo(
                                self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/ZRPreventShipping/ZRPreventShipping.php',
                                self::FORGED_ABSOLUTE_ROOT,
                            ),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/b/c', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/c', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/d', self::FORGED_ABSOLUTE_ROOT),
                        ],
                    ],
                ),
            ],
            'empty optionals' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'php ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/bin/phpcpd --fuzzy --exclude custom/plugins/ZRBannerSlider/ZRBannerSlider.php '
                            . '--exclude custom/plugins/ZRPreventShipping/ZRPreventShipping.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blabla/Installer.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blubblub/Installer.php .',
                    ],
                ),
            ],
            'excluding' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'php ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/bin/phpcpd --fuzzy --exclude a/ --exclude b/ '
                            . '--exclude custom/plugins/ZRBannerSlider/ZRBannerSlider.php '
                            . '--exclude custom/plugins/ZRPreventShipping/ZRPreventShipping.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blabla/Installer.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blubblub/Installer.php .',
                        'excluded' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_VENDOR . '/a', self::FORGED_ABSOLUTE_VENDOR),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_VENDOR . '/b', self::FORGED_ABSOLUTE_VENDOR),
                        ],
                    ],
                ),
            ],
            'extensions' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'php ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/bin/phpcpd --fuzzy --suffix argh --suffix wub '
                            . '--exclude custom/plugins/ZRBannerSlider/ZRBannerSlider.php '
                            . '--exclude custom/plugins/ZRPreventShipping/ZRPreventShipping.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blabla/Installer.php '
                            . '--exclude ' . self::FORGED_ABSOLUTE_ROOT . '/custom/plugins/blubblub/Installer.php .',
                        'extensions' => ['argh', 'wub'],
                    ],
                ),
            ],
            'targeted' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'php ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/bin/phpcpd --fuzzy c d',
                        'targets' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_VENDOR . '/c', self::FORGED_ABSOLUTE_VENDOR),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_VENDOR . '/d', self::FORGED_ABSOLUTE_VENDOR),
                        ],
                    ],
                ),
            ],
        ];
    }
}
