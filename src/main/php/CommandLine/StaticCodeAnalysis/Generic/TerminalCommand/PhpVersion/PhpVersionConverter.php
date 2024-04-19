<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

class PhpVersionConverter
{
    public function convertSemVerToPhpString(string $semVer): int
    {
        $digits = explode('.', $semVer);

        $major = (int) $digits[0] * 10000;
        $minor = isset($digits[1]) ? (int) $digits[1] * 100 : 0;
        $patch = isset($digits[2]) ? (int) $digits[2] : 0;

        $result = $major + $minor + $patch;

        return $result;
    }
}
