<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface DecorateEvent
{
    /** @return array<string> */
    public function getAllowedFileEndings(): array;

    public function getExclusionListToken(): string;

    /**
     * This annotation is needed for compatibility to symfony/console:4
     *
     * @return InputInterface
     */
    // phpcs:ignore
    public function getInput();

    /**
     * This annotation is needed for compatibility to symfony/console:4
     *
     * @return OutputInterface
     */
    // phpcs:ignore
    public function getOutput();

    public function getTerminalCommand(): TerminalCommand;
}
