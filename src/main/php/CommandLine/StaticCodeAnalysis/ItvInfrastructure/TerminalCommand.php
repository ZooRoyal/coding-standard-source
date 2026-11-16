<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\ItvInfrastructure;

use Override;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\Environment\Environment;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\AbstractTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\NoUsefulCommandFoundException;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;

class TerminalCommand extends AbstractTerminalCommand implements TargetTerminalCommand
{
    use TargetTrait;

    /** The npm binary which validates microservice.json files. */
    public const string VALIDATOR = 'validate-microservice-json';

    /** The directory a microservice keeps its infrastructure definition in. */
    private const string DEFAULT_DIRECTORY = 'infrastructure';

    private const string TEMPLATE = 'npx %1$s--no-install ' . self::VALIDATOR . ' %2$s';

    /**
     * TerminalCommand constructor.
     */
    public function __construct(private readonly Environment $environment)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function compile(): void
    {
        $sprintfCommand = sprintf(
            self::TEMPLATE,
            $this->buildPrefixString(),
            implode(' ', $this->findDirectories()),
        );

        $this->command = $sprintfCommand;
        $this->commandParts = explode(' ', $sprintfCommand);
    }

    /**
     * This method returns the directories to validate.
     *
     * The validator takes directories rather than files, because it validates the merged result of
     * microservice.json and every <environment>.microservice.json next to it. Handing it single
     * files would validate the overrides as if they were complete definitions.
     *
     * @return array<string>
     *
     * @throws NoUsefulCommandFoundException
     */
    private function findDirectories(): array
    {
        $this->validateTargets();

        if ($this->targetedFiles === null) {
            return [$this->findDefaultDirectory()];
        }

        $directories = array_map(
            static fn(EnhancedFileInfo $item): string => dirname($item->getRelativePathname()),
            $this->targetedFiles,
        );

        return array_values(array_unique($directories));
    }

    /**
     * This method returns the directory to validate if no targets were given.
     *
     * The coding standard runs in every ZooRoyal repository, most of which are not ITV
     * microservices. Without an infrastructure definition there is nothing to validate, which is a
     * reason to skip rather than to fail.
     *
     * @throws NoUsefulCommandFoundException
     */
    private function findDefaultDirectory(): string
    {
        $rootDirectory = $this->environment->getRootDirectory()->getRealPath();

        if (!is_file($rootDirectory . '/' . self::DEFAULT_DIRECTORY . '/microservice.json')) {
            throw new NoUsefulCommandFoundException(
                'There is no ' . self::DEFAULT_DIRECTORY . '/microservice.json to validate.',
                1763287200,
            );
        }

        return self::DEFAULT_DIRECTORY;
    }

    /**
     * This method returns the npx prefix pointing at the coding standard's own node_modules.
     */
    private function buildPrefixString(): string
    {
        $path = $this->environment->getVendorDirectory()->getRealPath() . '/..';

        return '--prefix ' . $path . ' ';
    }
}
