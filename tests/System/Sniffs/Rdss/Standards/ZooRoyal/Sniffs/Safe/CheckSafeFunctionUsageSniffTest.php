<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\System\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Zooroyal\CodingStandard\Tests\Tools\TestEnvironmentInstallation;

use function Safe\realpath;

class CheckSafeFunctionUsageSniffTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    public static function tearDownAfterClass(): void
    {
        TestEnvironmentInstallation::getInstance()->removeInstallation();
    }

    /**
     * @test
     *
     * @large
     */
    public function nagIfSafeIsNotInstalled(): void
    {
        $process = Process::fromShellCommandline('docker info');
        $process->run();
        if ($process->getExitCode() !== 0) {
            self::markTestSkipped('Docker is not running');
        }

        $installationPath = $this->prepareInstallationDirectory();

        $this->filesystem->copy(
            __DIR__ . '/fixtures/GoodPhp.php',
            $installationPath . '/src/GoodPhp.php'
        );

        $realpath = realpath(__DIR__ . '/../../../../../../../');
        $processCodingStandard = new Process(
            ['bash',  $realpath . '/run-coding-standard.sh', 'sca:sniff'],
            $installationPath
        );
        $processCodingStandard->setTimeout(480);
        $processCodingStandard->setIdleTimeout(120);
        $processCodingStandard->run();
        $processCodingStandard->wait();
        $output = $processCodingStandard->getOutput();

        self::assertMatchesRegularExpression('/No.*function.*names.*found!/ms', $output);
        self::assertMatchesRegularExpression('/Did.*you.*forget.*to.*install.*thecodingmachine\/Safe\?/ms', $output);
    }

    private function prepareInstallationDirectory(): string
    {
        $environment = TestEnvironmentInstallation::getInstance();
        if ($environment->isInstalled() === false) {
            $environment->addComposerJson(
                __DIR__
                . '/fixtures/composer-template.json',
            )->installComposerInstance(false);
        }
        return $environment->getInstallationPath();
    }
}
