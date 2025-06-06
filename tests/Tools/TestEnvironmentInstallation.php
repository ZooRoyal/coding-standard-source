<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Tools;

use BadMethodCallException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\json_encode;

class TestEnvironmentInstallation
{
    private static TestEnvironmentInstallation $instance;
    private readonly Filesystem $filesystem;
    private readonly string $installationPath;
    private string $composerJsonPath = '';
    private readonly string $composerPath;
    private bool $isInstalled = false;

    /**
     * The Constructor is private because this is a Singleton.
     */
    private function __construct()
    {
        $this->filesystem = new Filesystem();

        $dirname = random_int(74, 93485798397);
        $this->installationPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $dirname;

        $this->composerPath = dirname(__DIR__, 2);
    }

    /**
     * Add the path of the composer-template.json to build your test environment.
     *
     * @throws RuntimeException
     * @throws BadMethodCallException
     */
    public function addComposerJson(string $composerTemplatePath): self
    {
        if (!is_file($composerTemplatePath)) {
            throw new RuntimeException($composerTemplatePath . ' is not a valid path.', 1605083728);
        }
        if ($this->composerJsonPath !== '') {
            throw new BadMethodCallException('Composer.json is already set', 1605084542);
        }

        $this->composerJsonPath = $composerTemplatePath;

        return $this;
    }

    /**
     * Get the path of the current composer-template.json which will be used.
     *
     * @throws RuntimeException
     */
    public function getComposerJson(): string
    {
        if (!isset($this->composerJsonPath)) {
            throw new RuntimeException('Please set a valid path by using addComposerJson', 1605083729);
        }

        return $this->composerJsonPath;
    }

    public function getInstallationPath(): string
    {
        return $this->installationPath;
    }

    /**
     * Because of the lack of dependency injection in PHPUnit I present to you the Singleton AntiPattern.
     */
    public static function getInstance(): TestEnvironmentInstallation
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestEnvironmentInstallation();
        }
        return self::$instance;
    }

    /**
     * Actually install the test environment.
     */
    public function installComposerInstance(bool $installNpm = true): self
    {
        $this->filesystem->mkdir($this->installationPath);

        $this->installGit();
        $this->installComposer();
        $this->installNpm($installNpm);

        $this->isInstalled = true;

        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    /**
     * Removes the test environment completely
     */
    public function removeInstallation(): self
    {
        $this->filesystem->remove($this->installationPath);
        $this->isInstalled = false;
        $this->composerJsonPath = '';

        return $this;
    }

    private function installNpm(bool $installNpm): void
    {
        if ($installNpm) {
            $this->filesystem->remove($this->installationPath . '/vendor/zooroyal/coding-standard-source/node_modules');
            (new Process(
                [
                    'npm',
                    '--prefer-offline',
                    '--no-audit',
                    '--progress=false',
                    'install',
                    'vendor/zooroyal/coding-standard-source',
                ],
                $this->installationPath,
            ))->setIdleTimeout(60)->setTimeout(120)->mustRun();
        }
    }

    private function installGit(): void
    {
        (new Process(['git', 'init'], $this->installationPath))->mustRun();
    }

    private function installComposer(): void
    {
        $composerTemplate = json_decode(file_get_contents($this->getComposerJson()), true);
        $composerTemplate['repositories']['localRepo']['url'] = $this->composerPath;
        $renderedComposerFile = json_encode($composerTemplate);
        file_put_contents($this->installationPath . DIRECTORY_SEPARATOR . 'composer.json', $renderedComposerFile);

        (new Process(
            ['composer', 'install', '--no-interaction', '--no-progress'],
            $this->installationPath,
        ))
            ->setIdleTimeout(240)->setTimeout(480)->mustRun();

        $vendorBinSourceDirectory = $this->installationPath . '/vendor/zooroyal/coding-standard-source/vendor-bin';
        if ($this->filesystem->exists($vendorBinSourceDirectory)) {
            $this->filesystem->mirror(
                $vendorBinSourceDirectory,
                $this->installationPath . '/vendor-bin',
            );

            (new Process(
                ['composer', 'update', '--no-interaction', '--no-progress', '--no-suggest'],
                $this->installationPath,
            ))
                ->setIdleTimeout(240)->setTimeout(480)->mustRun();
        }
    }
}
