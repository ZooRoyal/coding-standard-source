<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;

interface MinimalVersionDependantTerminalCommand extends TerminalCommand
{
    /**
     * This method receives the minimal php version the source code to check is written in.
     */
    public function setMinimalPhpVersion(string $phpVersion): void;
}
