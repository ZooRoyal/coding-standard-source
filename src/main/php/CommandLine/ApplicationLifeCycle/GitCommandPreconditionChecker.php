<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle;

use Override;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zooroyal\CodingStandard\CommandLine\Process\ProcessRunner;

/**
 * Class CommandPreconditionChecker
 *
 * This EventSubscriber is meant to be subscribed to the EventDispatcher of the coding-standard application. It
 * subscribes to the event just before the first command is run and makes sure, that the command is run from inside a
 * git directory.
 */
class GitCommandPreconditionChecker implements EventSubscriberInterface
{
    private const string COMMAND = 'git rev-parse --git-dir';

    private ?int $exitCode = null;

    /**
     * CommandPreconditionChecker constructor.
     */
    public function __construct(private readonly ProcessRunner $processRunner)
    {
    }

    /**
     * Returns the command event to be subscribed to.
     *
     * @return array<string, array<int, int|string>>
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['checkForGit', 50]];
    }

    /**
     * Calls a git command to make sure, that the current working directory is managed by git. If so it does nothing.
     * If not a exception is thrown.
     *
     * @throws RuntimeException
     */
    public function checkForGit(): void
    {
        if ($this->exitCode === null) {
            $process = $this->processRunner->runAsProcessReturningProcessObject(self::COMMAND);

            $this->exitCode = $process->getExitCode();
        }

        if ($this->exitCode !== 0) {
            throw new RuntimeException(
                'The coding-standard CLI can\'t be used outside of a git context.',
                1612348705,
            );
        }
    }
}
