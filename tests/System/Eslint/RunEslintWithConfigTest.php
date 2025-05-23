<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\System\Eslint;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Process\Process;
use Amp\Promise;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Override;
use Symfony\Component\Filesystem\Filesystem;
use Zooroyal\CodingStandard\Tests\Tools\TestEnvironmentInstallation;

use function Amp\ByteStream\buffer;

class RunEslintWithConfigTest extends AsyncTestCase
{
    private const string EXPECTED_TS_PROBLEMS = '183 problems';
    private const string EXPECTED_JS_PROBLEMS = '183 problems';
    private const string ESLINT_COMMAND = 'npx --no-install eslint --config ';
    private const string ESLINT_CONFIG_FILE = 'vendor/zooroyal/coding-standard-source/config/eslint/eslint.config.js ';

    private Filesystem $filesystem;

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
     * @large
     * @coversNothing
     *
     * @return iterable<Promise<int>>
     */
    public function runEslintForJSInCleanInstall(): iterable
    {
        $testInstancePath = $this->prepareInstallationDirectory();

        $command = $this->getEslintCommand(
            'vendor/zooroyal/coding-standard-source/tests/System/fixtures/eslint/BadCode.js',
        );
        $commandArray = explode(' ', $command);
        $process = new Process($commandArray, $testInstancePath);

        yield $process->start();

        $output = yield buffer($process->getStdout());
        $errorOutput = yield buffer($process->getStdout());
        $exitCode = yield $process->join();

        self::assertSame(1, $exitCode, $errorOutput);

        MatcherAssert::assertThat($output, H::containsString(self::EXPECTED_JS_PROBLEMS));
    }

    /**
     * @test
     * @large
     * @coversNothing
     *
     * @return array<int,Promise>
     */
    public function runEslintForTSInCleanInstall(): iterable
    {
        $testInstancePath = $this->prepareInstallationDirectory();

        $command = $this->getEslintCommand(
            'vendor/zooroyal/coding-standard-source/tests/System/fixtures/eslint/BadCode.ts',
        );
        $commandArray = explode(' ', $command);
        $process = new Process($commandArray, $testInstancePath);

        yield $process->start();

        $exitCode = yield $process->join();
        $output = yield buffer($process->getStdout());
        $errorOutput = yield buffer($process->getStdout());

        self::assertSame(1, $exitCode, $errorOutput);

        MatcherAssert::assertThat($output, H::containsString(self::EXPECTED_TS_PROBLEMS));
    }

    /**
     * @test
     * @large
     * @coversNothing
     *
     * @return iterable<Promise>
     */
    public function runStylelintInCleanInstall(): iterable
    {
        $testInstancePath = $this->prepareInstallationDirectory();

        $command = 'vendor/bin/coding-standard sca:stylelint';
        $commandArray = explode(' ', $command);
        $process = new Process($commandArray, $testInstancePath);

        yield $process->start();

        $exitCode = yield $process->join();

        self::assertSame(0, $exitCode);
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
                    . '/fixtures/eslint/composer-template.json',
            )->installComposerInstance();
        }
        $envInstallationPath = $environment->getInstallationPath();
        $this->filesystem->copy(
            $envInstallationPath . '/vendor/zooroyal/coding-standard-source/tests/System/fixtures/eslint/tsconfig.json',
            $envInstallationPath . '/tsconfig.json',
        );
        return $envInstallationPath;
    }

    private function getEslintCommand(string $fileToCheck): string
    {
        return self::ESLINT_COMMAND
            . self::ESLINT_CONFIG_FILE
            . $fileToCheck;
    }
}
