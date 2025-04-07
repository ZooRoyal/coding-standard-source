<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\StaticCodeAnalysis\JSESLint;

use Hamcrest\Matchers;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\NoUsefulCommandFoundException;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\JSESLint\TerminalCommand;
use Zooroyal\CodingStandard\Tests\Tools\TerminalCommandTestData;

class TerminalCommandTest extends TestCase
{
    private const string FORGED_PACKAGE_DIRECTORY = '/packageDirectory';
    private const string FORGED_RELATIV_ROOT = '.';
    private const string FORGED_ABSOLUTE_ROOT = '/RootDirectory';
    private const string FORGED_ABSOLUTE_VENDOR = '/RootDirectory/vendor';

    private TerminalCommand $subject;
    private MockInterface|Environment $mockedEnvironment;

    private MockInterface|OutputInterface $mockedOutput;

    #[Override]
    protected function setUp(): void
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedOutput = Mockery::mock(OutputInterface::class);

        $this->mockedEnvironment->shouldReceive('getPackageDirectory->getRealPath')
            ->andReturn(self::FORGED_PACKAGE_DIRECTORY);
        $this->mockedEnvironment->shouldReceive('getRootDirectory->getRelativePathname')
            ->andReturn(self::FORGED_RELATIV_ROOT);
        $this->mockedEnvironment->shouldReceive('getVendorDirectory->getRealPath')
            ->andReturn(self::FORGED_ABSOLUTE_VENDOR);

        $this->subject = new TerminalCommand($this->mockedEnvironment);
        $this->subject->injectDependenciesAbstractTerminalCommand($this->mockedOutput);
    }

    #[Override]
    public function tearDown(): void
    {
        Mockery::close();
    }


    /**
     * @test
     */
    public function addInvalidVerbosityLevelThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1617802684);
        $this->expectExceptionMessage('Only verbosity settings from OutputInterface constants are allowed');
        $this->subject->addVerbosityLevel(99999);
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
                    '<info>Compiled TerminalCommand to following string</info>'
                    . PHP_EOL . $data->getExpectedCommand(),
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );

        foreach ($data->getExcluded() as $exclude) {
            $this->subject->addExclusions([$exclude]);
        }
        if ($data->getTargets() !== null) {
            foreach ($data->getTargets() as $target) {
                $this->subject->addTargets([$target]);
            }
        }

        $this->subject->addAllowedFileExtensions($data->getExtensions());
        $this->subject->setFixingMode($data->isFixing());
        $this->subject->addVerbosityLevel($data->getVerbosityLevel());

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
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR . '/.. --no-install eslint '
                            . '--quiet --fix --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY
                            . '/config/eslint/eslint.config.js --ext qweasd --ext argh --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY
                            . '/config/eslint/.eslintignore --ignore-pattern a --ignore-pattern b c d',
                        'excluded' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/a', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/b', self::FORGED_ABSOLUTE_ROOT),
                        ],
                        'extensions' => ['qweasd', 'argh'],
                        'fixingMode' => true,
                        'targets' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/c', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/d', self::FORGED_ABSOLUTE_ROOT),
                        ],
                        'verbosityLevel' => OutputInterface::VERBOSITY_QUIET,
                    ],
                ),
            ],
            'empty optionals' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                    ],
                ),
            ],
            'excluding' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY
                            . '/config/eslint/.eslintignore --ignore-pattern a --ignore-pattern b '
                            . self::FORGED_RELATIV_ROOT,
                        'excluded' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/a', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/b', self::FORGED_ABSOLUTE_ROOT),
                        ],
                    ],
                ),
            ],
            'extensions' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY
                            . '/config/eslint/eslint.config.js --ext asdqwe --ext qweasd --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'extensions' => ['asdqwe', 'qweasd'],
                    ],
                ),
            ],
            'fixing' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --fix --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'fixingMode' => true,
                    ],
                ),
            ],
            'targeted' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore c d',
                        'targets' => [
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/c', self::FORGED_ABSOLUTE_ROOT),
                            new EnhancedFileInfo(self::FORGED_ABSOLUTE_ROOT . '/d', self::FORGED_ABSOLUTE_ROOT),
                        ],
                    ],
                ),
            ],
            'verbosity quiet' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --quiet --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'verbosityLevel' => OutputInterface::VERBOSITY_QUIET,
                    ],
                ),
            ],
            'verbosity verbose' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --debug --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'verbosityLevel' => OutputInterface::VERBOSITY_VERBOSE,
                    ],
                ),
            ],
            'verbosity very verbose' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --debug --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'fixingMode' => false,
                        'verbosityLevel' => OutputInterface::VERBOSITY_VERY_VERBOSE,
                    ],
                ),
            ],
            'verbosity debug verbose' => [
                new TerminalCommandTestData(
                    [
                        'expectedCommand' => 'npx --prefix ' . self::FORGED_ABSOLUTE_VENDOR
                            . '/.. --no-install eslint --debug --no-error-on-unmatched-pattern --no-eslintrc --config '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/eslint.config.js --ignore-path '
                            . self::FORGED_PACKAGE_DIRECTORY . '/config/eslint/.eslintignore '
                            . self::FORGED_RELATIV_ROOT,
                        'verbosityLevel' => OutputInterface::VERBOSITY_DEBUG,
                    ],
                ),
            ],

        ];
    }

    /**
     * @test
     */
    public function terminalCommandCompilationThrowsExceptionOnNoFilesToCheck(): void
    {
        $this->expectException(NoUsefulCommandFoundException::class);
        $this->expectExceptionCode(1620831304);
        $this->expectExceptionMessage('It makes no sense to sniff no files.');

        $this->subject->addTargets([]);

        $this->subject->__toString();
    }
}
