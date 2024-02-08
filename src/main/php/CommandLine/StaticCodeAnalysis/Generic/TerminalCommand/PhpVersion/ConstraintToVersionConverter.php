<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion;

use Composer\Semver\Semver;

use function Safe\preg_match;

class ConstraintToVersionConverter
{
    /** @var array<string> */
    private array $phpVersions = [];

    public function __construct()
    {
        $phpVersionRanges = [
            '7.4.' => '33',
            '8.0.' => '27',
            '8.1.' => '17',
            '8.2.' => (explode('.', phpversion()))[2],
        ];

        foreach ($phpVersionRanges as $phpVersionString => $phpMaxPatchVersion) {
            $phpPatchLevels = range('0', $phpMaxPatchVersion);
            foreach ($phpPatchLevels as $phpPatchLevel) {
                $this->phpVersions[] = $phpVersionString . $phpPatchLevel;
            }
        }
    }

    /**
     * Check if $phpVersionConstraint is a version number and return it or if we find a php version that satisfies
     * the constraint.
     */
    public function extractActualPhpVersion(string $phpVersionConstraint): string
    {
        if (preg_match('/^(\d+)(\.\d)?(\.\d)?$/', $phpVersionConstraint, $matches)) {
            return $matches[1] . ($matches[2] ?? '.0') . ($matches[3] ?? '.0');
        }

        $minPhpVersion = '7.4.0';
        foreach ($this->phpVersions as $phpVersion) {
            if (SemVer::satisfies($phpVersion, $phpVersionConstraint)) {
                $minPhpVersion = $phpVersion;
                break;
            }
        }
        return $minPhpVersion;
    }
}
