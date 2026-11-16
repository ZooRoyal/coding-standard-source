<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\ItvInfrastructure;

use DI\Attribute\Inject;
use DI\Container;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandFinder;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\NpmAppFinder\NpmCommandNotFoundException;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TargetableToolsCommand;

class ItvInfrastructureCommand extends TargetableToolsCommand
{
    protected string $exclusionListToken = '.dontItvInfrastructure';

    /**
     * The validator looks at microservice.json and at every <environment>.microservice.json next to
     * it. Both end in 'microservice.json', which is all the file filter compares.
     *
     * @var array<string>
     */
    protected array $allowedFileEndings = ['microservice.json'];
    private NpmCommandFinder $terminalCommandFinder;

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->setName('sca:itv-infrastructure');
        $this->setDescription('Validate ITV microservice.json infrastructure definitions.');
        $this->setHelp(
            'This tool validates infrastructure/microservice.json against the ITV infrastructure '
            . 'schema and the rules the schema cannot express. Repositories without such a file are '
            . 'skipped. Add a .dontItvInfrastructure file to directories that should be ignored.',
        );
        $this->terminalCommandName = 'ITV Infrastructure Validation';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->terminalCommandFinder->findTerminalCommand(TerminalCommand::VALIDATOR);
        } catch (NpmCommandNotFoundException) {
            $output->writeln(
                '<info>' . TerminalCommand::VALIDATOR . ' could not be found. '
                . 'To use this sniff please refer to the README.md</info>',
            );
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
