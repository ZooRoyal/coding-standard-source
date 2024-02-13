<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

trait MinimalVersionDependentTrait
{
    protected string $minimalPhpVersion = '7.4';

    /**
     * {@inheritDoc}
     */
    public function setMinimalPhpVersion(string $minimalPhpVersion): void
    {
        $this->minimalPhpVersion = $minimalPhpVersion;
    }
}
