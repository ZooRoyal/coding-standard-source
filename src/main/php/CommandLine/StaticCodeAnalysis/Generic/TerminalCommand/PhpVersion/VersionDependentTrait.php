<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

trait VersionDependentTrait
{
    protected string $phpVersion = '7.4';

    /**
     * {@inheritDoc}
     */
    public function setPhpVersion(string $phpVersion): void
    {
        $this->phpVersion = $phpVersion;
    }
}
