<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan;

use DI\Attribute\Inject;
use DI\Container;
use Override;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TargetableToolsCommand;

class PHPStanCommand extends TargetableToolsCommand
{
    public const EXCLUSION_LIST_TOKEN = '.dontStanPHP';

    /** @var string string */
    protected string $exclusionListToken = self::EXCLUSION_LIST_TOKEN;
    /** @var array<string> */
    protected array $allowedFileEndings = ['.php'];

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->setName('sca:stan');
        $this->setDescription('Run PHPStan on PHP files.');
        $this->setHelp(
            'This tool executes PHPStan on a certain set of PHP files of this project.'
            . 'It ignores files which are in directories with a .dontStanPHP file. Subdirectories are ignored too.',
        );
        $this->terminalCommandName = 'PHPStan';
    }

    /**
     * This method accepts all dependencies needed to use this class properly.
     * It's annotated for use with PHP-DI.
     *
     * @see http://php-di.org/doc/annotations.html
     */
    #[Inject]
    public function injectDependenciesCommand(Container $container): void
    {
        $this->terminalCommand = $container->make(TerminalCommand::class);
    }
}
