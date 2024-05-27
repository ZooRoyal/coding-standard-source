<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class MinimalVersionDecorator extends TerminalCommandDecorator
{
    public function __construct(private readonly ComposerInterpreter $composerInterpreter)
    {
    }

    public function decorate(DecorateEvent $event): void
    {
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof MinimalVersionDependantTerminalCommand) {
            return;
        }

        $minimalPhpVersion = $this->composerInterpreter->getMinimalRootPackagePhpVersion();
        $terminalCommand->setMinimalPhpVersion($minimalPhpVersion);

        $event->getOutput()->writeln(
            '<info>Targeted minimal PHP version is ' . $minimalPhpVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
    }
}
