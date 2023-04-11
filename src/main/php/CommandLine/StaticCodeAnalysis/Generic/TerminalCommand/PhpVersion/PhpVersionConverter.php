<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

class PhpVersionConverter
{
    public function convertSemVerToPhpString(string $semVer): int
    {
        $versionTemplate = '%d0%d0%d';

        $digits = explode('.', $semVer);
        $digits = array_pad($digits, 3, 0);

        $result = (int) sprintf($versionTemplate, ...$digits);
        return $result;
    }
}
