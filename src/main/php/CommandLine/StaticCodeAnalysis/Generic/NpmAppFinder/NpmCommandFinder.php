<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder;

use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;

class NpmCommandFinder
{
    /**
     * TerminalCommandFinder constructor.
     */
    public function __construct(
        private readonly ProcessRunner $processRunner,
        private readonly Environment $environment,
    ) {
    }

    /**
     * Finds path to command.
     *
     * @throws NpmCommandNotFoundException
     */
    public function findTerminalCommand(string $commandName): string
    {
        $installationPath = $this->environment->getVendorDirectory()->getRealPath() . '/..';

        $exitCode = $this->processRunner->runAsProcessReturningProcessObject(
            'npx --prefix ' . $installationPath . ' --no-install ' . $commandName . ' --help',
        )->getExitCode();

        if ($exitCode !== 0) {
            throw new NpmCommandNotFoundException(
                ucfirst($commandName) . ' could not be found in path or by npm.',
                1595949828,
            );
        }

        return 'npx --no-install ' . $commandName;
    }
}
