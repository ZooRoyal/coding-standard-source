<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\System\Complete;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Zooroyal\CodingStandard\Tests\Tools\TestEnvironmentInstallation;

class GlobalSystemTest extends TestCase
{
    private Filesystem $filesystem;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        $dockerCheckProcess = new Process(['docker', 'info']);
        $dockerCheckProcess->run();
        if ($dockerCheckProcess->getExitCode() !== 0) {
            self::markTestSkipped('Docker is not available. Skipping Test.');
        }
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        TestEnvironmentInstallation::getInstance()->removeInstallation();
    }

    /**
     * @test
     *
     * @large
     * @coversNothing
     *
     * @depends runCodingStandardToFindErrors
     */
    public function dontFilesMakeAllGood(): void
    {
        $environmentDirectory = $this->prepareInstallationDirectory();
        $badCodeDirectory = $environmentDirectory . DIRECTORY_SEPARATOR . 'BadCode';

        $dotFiles = [
            '.dontSniffPHP',
            '.dontMessDetectPHP',
            '.dontCopyPasteDetectPHP',
            '.dontLintPHP',
            '.dontSniffLESS',
            '.dontSniffJS',
            '.dontStanPHP',
        ];

        foreach ($dotFiles as $dotFile) {
            $this->filesystem->dumpFile($badCodeDirectory . DIRECTORY_SEPARATOR . $dotFile, '');
        }

        $result = $this->runTools($environmentDirectory, false);

        MatcherAssert::assertThat('All Tools should be satisfied!', $result, H::not(H::hasItems(H::greaterThan(0))));
    }

    /**
     * @test
     *
     * @large
     * @coversNothing
     */
    public function runCodingStandardToFindErrors(): void
    {
        $environmentDirectory = $this->prepareInstallationDirectory();

        $this->copyFilesForFindErrorsTest($environmentDirectory);

        $result = $this->runTools($environmentDirectory, true);

        MatcherAssert::assertThat('All tools should be throwing an error.', $result, H::not(H::hasItems(0)));
    }

    /**
     * Provides an composer environment to run tests on.
     */
    private function prepareInstallationDirectory(): string
    {
        $environment = TestEnvironmentInstallation::getInstance();
        if ($environment->isInstalled() === false) {
            $environment->addComposerJson(
                dirname(__DIR__)
                . '/fixtures/complete/composer-template.json',
            )->installComposerInstance();
        }
        return $environment->getInstallationPath();
    }

    /**
     * Run all available coding-standard tools in $environmentDirectory and returns promises for use in Amp.
     *
     * @return array<string,int>
     */
    private function runTools(string $environmentDirectory, bool $errorsAreGood = false): array
    {
        $tools = [
            'sca:sniff',
            'sca:mess',
            'sca:para',
            'sca:copy',
            'sca:stan',
            'sca:style',
            'sca:eslint',
        ];

        /** @var array<Process> $processes */
        $processes = [];
        /** @var array<string,int> $existCodes */
        $existCodes = [];

        foreach ($tools as $tool) {
            $processes[$tool] = new Process(
                [$environmentDirectory . '/vendor/bin/coding-standard', $tool],
                $environmentDirectory,
            );

            $processes[$tool]->setTimeout(120);
            $processes[$tool]->setIdleTimeout(60);
            $processes[$tool]->run();
            $existCodes[$tool] = $processes[$tool]->getExitCode();

            if (($existCodes[$tool] === 0) === $errorsAreGood) {
                $this->echoOutput($processes[$tool]);
            }
        }

        return $existCodes;
    }

    /**
     * Writes unexpected tool output to test log.
     */
    private function echoOutput(Process $process): void
    {
        echo PHP_EOL . PHP_EOL . 'UNEXPECTED TOOL ' . TestEnvironmentInstallation::getInstance()->getInstallationPath()
            . ':' . PHP_EOL;

        echo $process->getOutput();
        echo $process->getErrorOutput();
    }

    private function copyFilesForFindErrorsTest(string $environmentDirectory): void
    {
        $codingStandardDirectory = dirname(__DIR__, 3);
        $fixtureDirectory = $codingStandardDirectory . '/tests/System/fixtures';
        $badCodeDirectory = $environmentDirectory . DIRECTORY_SEPARATOR . 'BadCode';
        $mockedPluginDirectory = $environmentDirectory . '/custom/plugins';
        $badPhpSnifferFilePath = dirname(__DIR__, 2)
            . '/Functional/Sniffs/PHPCodesniffer/Standards/ZooRoyal/Sniffs/Commenting/'
            . 'Fixtures/FixtureIncorrectComments.php';

        $this->filesystem->mkdir($badCodeDirectory);

        $copyFiles = [
            [$codingStandardDirectory . '/.gitignore', $environmentDirectory . '/.gitignore'],
            [
                $fixtureDirectory . '/complete/GoodPhp.php',
                $environmentDirectory . '/custom/plugins/blubblub/Installer.php',
            ],
            [
                $fixtureDirectory . '/complete/GoodPhp.php',
                $environmentDirectory . '/custom/plugins/blabla/Installer.php',
            ],
            [$fixtureDirectory . '/complete/GoodPhp.php', $environmentDirectory . '/GoodPhp.php'],
            [$fixtureDirectory . '/complete/GoodPhp2.php', $environmentDirectory . '/GoodPhp2.php'],
            [$fixtureDirectory . '/eslint/BadCode.ts', $badCodeDirectory . '/BadCode.ts'],
            [$fixtureDirectory . '/stylelint/BadCode.less', $badCodeDirectory . '/BadCode.less'],
            [$badPhpSnifferFilePath, $badCodeDirectory . '/BadSniffer.php'],
            [__FILE__, $badCodeDirectory . '/BadCopyPasteDetect1.php'],
            [__FILE__, $badCodeDirectory . '/BadCopyPasteDetect2.php'],
            [$fixtureDirectory . '/complete/Installer.php', $mockedPluginDirectory . '/a/Installer.php'],
            [$fixtureDirectory . '/complete/Installer2.php', $mockedPluginDirectory . '/b/Installer.php'],
            [$fixtureDirectory . '/complete/BadStan.php', $badCodeDirectory . '/BadStan.php'],
            [$fixtureDirectory . '/complete/badLint.php', $badCodeDirectory . '/badLint.php'],
            [$fixtureDirectory . '/complete/BadMessDetect.php', $badCodeDirectory . '/BadMessDetect.php'],
        ];

        foreach ($copyFiles as $copyFile) {
            $this->filesystem->copy($copyFile[0], $copyFile[1]);
        }
    }
}
