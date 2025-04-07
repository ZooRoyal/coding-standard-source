<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\Process;

use Symfony\Component\Process\Process;

use function Safe\getcwd;

class ProcessRunner
{
    /**
     * Creates a preconfigured Process.
     */
    public function createProcess(string $command, string ...$arguments): Process
    {
        $process = new Process([...explode(' ', $command), ...$arguments], getcwd());

        $process->setTimeout(null);
        $process->setIdleTimeout(120);

        return $process;
    }

    /**
     * Runs a shell command as single Process and returns the result.
     *
     * @param string ...$arguments Multiple strings interpreted as Arguments
     */
    public function runAsProcess(string $command, string ...$arguments): string
    {
        $process = $this->createProcess($command, ...$arguments);
        $process->mustRun();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        $result = $errorOutput === '' || $errorOutput === '0'
            ? $output
            : $output . PHP_EOL . $errorOutput;

        return trim($result);
    }

    /**
     * Runs a shell command as single Process and returns the result.
     */
    public function runAsProcessReturningProcessObject(string $command): Process
    {
        $process = $this->createProcess($command);
        $process->run();

        return $process;
    }
}
