<?php

declare(strict_types=1);

use DI\Container;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ApplicationFactory;
use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\EventDispatcherFactory;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\GitIgnoresExcluder;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\GitPathsExcluder;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\StaticExcluder;
use Zooroyal\CodingStandard\CommandLine\ExclusionList\Excluders\TokenExcluder;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FastCachedFileSearch;
use Zooroyal\CodingStandard\CommandLine\FileSearch\FileSearchInterface;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\PhpVersion\ComposerInterpreter;

use function DI\factory;
use function DI\get;

return [
    Application::class => factory(ApplicationFactory::class . '::build'),
    EventDispatcherInterface::class => factory(EventDispatcherFactory::class . '::build'),
    FileSearchInterface::class => get(FastCachedFileSearch::class),
    InputInterface::class => get(ArgvInput::class),
    OutputInterface::class => get(ConsoleOutput::class),
    Parser::class => factory(
        static function (ContainerInterface $container, ComposerInterpreter $composerInterpreter) {
            $phpversion = PhpVersion::fromString($composerInterpreter->getMinimalViablePhpVersion());
            return $container->get(ParserFactory::class)->createForVersion($phpversion);
        }
    ),

    'excluders' => factory(
        static function (Container $container) {
            $result[] = $container->get(StaticExcluder::class);
            $result[] = $container->get(GitIgnoresExcluder::class);
            $result[] = $container->get(GitPathsExcluder::class);
            $result[] = $container->get(TokenExcluder::class);
            return $result;
        },
    ),
];
