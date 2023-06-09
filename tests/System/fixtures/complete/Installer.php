<?php

declare(strict_types=1);

namespace Zooroyal\a;

use SplFileInfo;
use Webmozart\PathUtil\Path;

class Installer extends SplFileInfo
{
    private string $relativePathname;

    public function __construct(string $pathname, string $basePath)
    {
        parent::__construct($pathname);
        $this->relativePathname = Path::makeRelative($pathname, $basePath);
        $this->relativePathname = empty($this->relativePathname) ? '.' : $this->relativePathname;
    }

    /**
     * Returns the relative path name.
     *
     * This path contains the file name.
     */
    public function getRelativePathname(): string
    {
        return $this->relativePathname;
    }

    /**
     * Checks if the path name ends with the given suffix.
     */
    public function endsWith(string $suffix): bool
    {
        return str_ends_with($this->getPathname(), $suffix);
    }

    /**
     * Checks if the path name starts with the given prefix.
     */
    public function startsWith(string $suffix): bool
    {
        return str_starts_with($this->getPathname(), $suffix);
    }

    /**
     * Returns the relative path name.
     *
     * This path contains the file name.
     */
    public function getRelativePathname1(): string
    {
        return $this->relativePathname;
    }

    /**
     * Checks if the path name ends with the given suffix.
     */
    public function endsWit1h(string $suffix): bool
    {
        return str_ends_with($this->getPathname(), $suffix);
    }

    /**
     * Checks if the path name starts with the given prefix.
     */
    public function startsWith1(string $suffix): bool
    {
        return str_starts_with($this->getPathname(), $suffix);
    }
}
