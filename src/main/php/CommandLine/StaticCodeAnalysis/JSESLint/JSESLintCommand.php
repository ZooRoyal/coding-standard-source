<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\JSESLint;

use DI\Attribute\Inject;
use DI\Container;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\FixingToolCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandFinder;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandNotFoundException;

class JSESLintCommand extends FixingToolCommand
{
    /** @var string string */
    protected string $exclusionListToken = '.dontSniffJS';
    /** @var array<string>  */
    protected array $allowedFileEndings = ['js', 'ts', 'jsx', 'tsx'];
    private NpmCommandFinder $terminalCommandFinder;

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->setName('sca:eslint');
        $this->setDescription('Run ESLint on JS files.');
        $this->setHelp(
            'This tool executes ESLINT on a certain set of JS files of this project.'
            . ' Add a .dontSniffJS file to <JS-DIRECTORIES> that should be ignored.',
        );
        $this->terminalCommandName = 'EsLint';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->terminalCommandFinder->findTerminalCommand('eslint');
        } catch (NpmCommandNotFoundException) {
            $output->writeln('<info>EsLint could not be found. To use this sniff please refer to the README.md</info>');
            return 0;
        }
        return parent::execute($input, $output);
    }

    /**
     * This method accepts all dependencies needed to use this class properly.
     * It's annotated for use with PHP-DI.
     *
     * @see http://php-di.org/doc/annotations.html
     */
    #[Inject]
    public function injectDependenciesCommand(Container $container, NpmCommandFinder $terminalCommandFinder): void
    {
        $this->terminalCommandFinder = $terminalCommandFinder;
        $this->terminalCommand = $container->make(TerminalCommand::class);
    }
}
