<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Override;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class VersionDecorator extends TerminalCommandDecorator
{
    public function __construct(
        private readonly ComposerInterpreter $composerInterpreter,
    ) {
    }

    #[Override]
    public function decorate(DecorateEvent $event): void
    {
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof VersionDependentTerminalCommand) {
            return;
        }

        $phpVersion = $this->composerInterpreter->getMinimalViablePhpVersion();
        $terminalCommand->setPhpVersion($phpVersion);

        $event->getOutput()->writeln(
            '<info>Targeted PHP version is ' . $phpVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
    }
}
