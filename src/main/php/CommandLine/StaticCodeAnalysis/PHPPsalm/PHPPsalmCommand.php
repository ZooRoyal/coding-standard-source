<?php

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPPsalm;

use DI\Attribute\Inject;
use DI\Container;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\FixingToolCommand;

class PHPPsalmCommand extends FixingToolCommand
{
    /** @var string string */
    protected string $exclusionListToken = '.dontPsalmPHP';
    /** @var array<string> */
    protected array $allowedFileEndings = ['php'];

    protected function configure(): void
    {
        parent::configure();
        $this->setName('sca:psalm');
        $this->setDescription('Run Psalm on PHP files.');
        $this->setHelp(
            'This tool executes Psalm on a certain set of PHP files of this project. '
            . 'It ignores files which are in directories with a .dontPsalmPHP file. Subdirectories are ignored too.',
        );
        $this->terminalCommandName = 'PHP Psalm';
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

