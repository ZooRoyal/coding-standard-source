<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class MinimalVersionDecorator extends TerminalCommandDecorator
{
    private ?string $cachedMinPhpVersion = null;

    public function __construct(
        private readonly ConstraintToVersionConverter $constraintToVersionConverter,
        private readonly ComposerInterpreter $composerInterpreter,
    ) {
    }

    public function decorate(DecorateEvent $event): void
    {
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof MinimalVersionDependantTerminalCommand) {
            return;
        }

        if ($this->cachedMinPhpVersion === null) {
            $phpVersionConstraint = $this->composerInterpreter->getLocalPhpVersionConstraint();

            $this->cachedMinPhpVersion = $this->constraintToVersionConverter
                ->extractActualPhpVersion($phpVersionConstraint);
        }

        $terminalCommand->setMinimalPhpVersion($this->cachedMinPhpVersion);

        $event->getOutput()->writeln(
            '<info>Targeted minimal PHP version is ' . $this->cachedMinPhpVersion . '</info>' . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE,
        );
    }
}
