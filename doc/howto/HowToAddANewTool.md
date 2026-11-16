# How to add a new static code analysis tool

> The code in this document is meant to compile. If it ever stops matching the real classes,
> `ApplicationFactory` and the directories under
> `src/main/php/CommandLine/StaticCodeAnalysis/Generic/TerminalCommand/` are the source of truth.

## Gather information

First of all you need to gather some information about your new tool.

1. Is the tool able to only work on files you provide by a command-line
   parameter? \
   For example Parallel Lint is able to only work on certain files.
   ```bash
   vendor/bin/parallel-lint aFileIWantToCheck.php
   ```
2. Is the tool able to fix violations in a file? \
   For example ESLint is able to fix violations.
   ```bash
   eslint -f aFileIWantToFix.js
   ```

## Add your code

Let's say you want to add your new tool 'SuperCoolTool' to the coding-standard

1. Create a new namespace
   `Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\SuperCoolTool`.
2. Create two new Classes in your new namespace: `SuperCoolToolCommand` and `TerminalCommand`.
3. Register your new command — in **two** places.

The next paragraphs will guide you through that process.

### Implement SuperCoolToolCommand

This class stores some vital information about the SuperCoolTool. The
coding-standard will use this information to integrate your tool in its cli.

There are 3 base classes to chose from, depending on what capabilities of
your tool you want to expose to the user. They differ in the command-line options they add:

| Base class                | Options it adds                                 | Constructor parameters                             |
|---------------------------|-------------------------------------------------|----------------------------------------------------|
| `AbstractToolCommand`     | none                                            | `?string $name = null`                             |
| `TargetableToolsCommand`  | `-t/--target`, `-a/--auto-target`               | `TargetableInputFacet`, `?string $name = null`     |
| `FixingToolCommand`       | the above plus `-f/--fix`                       | `FixableInputFacet`, `TargetableInputFacet`, `?string $name = null` |

The facets are injected by PHP-DI, so you never construct them yourself — but your unit test does,
which is why the parameter order matters.

We are going to assume a FixingToolCommand for our SuperCoolTool - its SuperCool after all!

```php
<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\SuperCoolTool;

use DI\Attribute\Inject;
use DI\Container;
use Override;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\FixingToolCommand;

class SuperCoolToolCommand extends FixingToolCommand
{
    // What file makes your tool ignore a directory
    protected string $exclusionListToken = '.dontBeSuperCool';

    // Do you only want to check for certain file types? If not leave empty.
    // These are matched with str_ends_with against the whole path name, so 'sc' also matches
    // anything ending in '.sc'.
    /** @var array<string> */
    protected array $allowedFileEndings = ['sc'];

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        // Do this... always. It is what adds the options of your base class.
        parent::configure();

        // Choose the command to use for your tool
        $this->setName('sca:supercool');

        // Choose a description
        $this->setDescription('Run the SuperCool tool');

        // Write a helpful message for your enduser
        $this->setHelp(
            'This tool executes the SuperCoolTool on a certain set of SC files '
            . 'of this project. It ignores files which are in directories with a '
            . '.dontBeSuperCool file. Subdirectories are ignored too.',
        );

        // Choose by which name your tool will be referenced by the coding-standard
        $this->terminalCommandName = 'The Super Cool Tool';
    }

    /**
     * This method accepts all dependencies needed to use this class properly.
     * It's annotated for use with PHP-DI.
     *
     * @see http://php-di.org/doc/attributes.html
     */
    #[Inject]
    public function injectDependenciesCommand(Container $container): void
    {
        // Create an instance of your TerminalCommand and store it in this property.
        // You may use the container like so but it really doesn't matter as long as
        // an instance of your TerminalCommand will be stored in this property
        $this->terminalCommand = $container->make(TerminalCommand::class);
    }
}
```

Three details that will cost you an afternoon if you get them wrong:

* `configure()` is `protected ... : void`. `Command::configure()` declares that signature, and PHP
  rejects an override that drops the return type.
* `#[Inject]` is an **attribute**. This repository is on php-di 7 with `useAttributes(true)`;
  version 7 removed docblock annotations, so an `@Inject` docblock is silently ignored and
  `$this->terminalCommand` stays uninitialized.
* Mark overrides with `#[Override]` and start every file with `declare(strict_types=1)`. The
  coding-standard sniffs itself and will tell you about both.

### Implement your TerminalCommand

The coding-standard will inject your TerminalCommand with important
information about the user input and context of the system, but you have to tell it what
information you would like to get. For this reason we are using interfaces.

Each aspect lives in its own directory under
`StaticCodeAnalysis/Generic/TerminalCommand/` and consists of a triple: an interface to implement,
a trait with the default implementation, and a decorator that pushes the data in. These exist
today:

| Directory      | Interface                             | Trait                          | Field it fills                             |
|----------------|---------------------------------------|--------------------------------|--------------------------------------------|
| `Target`       | `TargetTerminalCommand`               | `TargetTrait`                  | `?array $targetedFiles` (`null` = no target given) |
| `Fix`          | `FixTerminalCommand`                  | `FixTrait`                     | `bool $fixingMode`                         |
| `Exclusion`    | `ExclusionTerminalCommand`            | `ExclusionTrait`               | `array $excludesFiles`                     |
| `Extension`    | `FileExtensionTerminalCommand`        | `FileExtensionTrait`           | `array $fileExtensions`                    |
| `Verbose`      | `VerboseTerminalCommand`              | `VerboseTrait`                 | `int $verbosityLevel`                      |
| `Multiprocess` | `MultiprocessTerminalCommand`         | `MultiprocessTrait`            | `int $maxConcurrentProcesses`              |
| `PhpVersion`   | `VersionDependentTerminalCommand`     | `VersionDependentTrait`        | `string $phpVersion`                       |
| `PhpVersion`   | `MinimalVersionDependantTerminalCommand` | `MinimalVersionDependentTrait` | `string $minimalPhpVersion`             |

If none of them supplies what you need, see
[how to add information sources](HowToAddInformationSources.md).

In case of your SuperCoolTool we will get information about the files it
should be checking and the fixing mode.

```php
<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\SuperCoolTool;

use Override;
use Zooroyal\CodingStandard\CommandLine\EnhancedFileInfo\EnhancedFileInfo;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\AbstractTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Fix\FixTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Fix\FixTrait;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTerminalCommand;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\Target\TargetTrait;

// To make your life more easy just use AbstractTerminalCommand as base for your new class.
// Now we tell the coding-standard which kind of TerminalCommand this is.
class TerminalCommand extends AbstractTerminalCommand implements TargetTerminalCommand, FixTerminalCommand
{
    // So you don't need to hassle with setters and such, simply use the
    // provided traits. The coding-standard provides a trait for every
    // TerminalCommand interface.
    use TargetTrait;
    use FixTrait;

    /**
     * This method must implement the compilation of the command. Technically it has
     * one shot in setting the protected fields $command and $commandParts.
     *
     * {@inheritDoc}
     */
    #[Override]
    protected function compile(): void
    {
        // TargetTrait ships this guard. It throws NoUsefulCommandFoundException if targeting
        // found no files at all, which makes the coding-standard skip your tool instead of
        // running it against everything.
        $this->validateTargets();

        $targets = implode(
            ' ',
            array_map(
                static fn(EnhancedFileInfo $item): string => $item->getRelativePathname(),
                $this->targetedFiles ?? [],
            ),
        );

        // Now it's your turn to use the information supplied to the TerminalCommand
        // to generate a string and an array which may be thrown to the command line
        // to execute your SuperCoolTool. As this may be tricky feel free to have a look
        // at the already implemented tools.

        // Don't forget to set the result to the appropriate fields
        $this->command = 'supercool ' . ($this->fixingMode ? '--fix ' : '') . $targets;
        $this->commandParts = explode(' ', $this->command);
    }
}
```

#### Skipping instead of failing

`compile()` is the right place to decide that there is nothing to do. Throw a
`NoUsefulCommandFoundException` and `AbstractToolCommand::execute()` prints `Skipping tool.`, the
reason in verbose mode, and returns exit code 0. Every other exception is wrapped into a
`RuntimeException` and fails the run.

Two caveats worth knowing before you rely on `validateTargets()` alone:

* `$targetedFiles` is `null`, not `[]`, when the user passed neither `-t` nor `-a`. The guard does
  not fire then, because "no target given" means "check everything".
* If your tool is only applicable to some repositories, check that yourself and throw. The
  coding-standard runs in repositories that have never heard of your tool.

If the tool itself may be missing — an npm binary, say — catch that in the command's `execute()`
and return 0 with a hint, the way `JSStyleLintCommand` does.

### Register your new Tool

You implemented and tested `SuperCoolToolCommand` and `TerminalCommand`... now what?

To let coding-standard know there is a fancy new tool in town, add it to the list in
`Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ApplicationFactory`.

```php
class ApplicationFactory
{
    /** @var array<string> */
    private const array COMMANDS
        = [
            AllToolsCommand::class,
            FindFilesToCheckCommand::class,
            PHPCodeSnifferCommand::class,
            PHPCopyPasteDetectorCommand::class,
            PHPParallelLintCommand::class,
            PHPStanCommand::class,
            JSESLintCommand::class,
            JSStyleLintCommand::class,
            SuperCoolToolCommand::class, // Just add your class to the list
        ];

    // [...]
}
```

There is a **second copy of that list** in
`Zooroyal\CodingStandard\Tests\Unit\CommandLine\ApplicationLifeCycle\ApplicationFactoryTest::$commands`.
It is not generated from the first one. If you forget it, `ApplicationFactoryTest::build` fails with
`No matching handler found for Mockery_0_DI_Container::get(...)`.

```php
    /** @var array<string> */
    private array $commands
        = [
            // [...]
            SuperCoolToolCommand::class,
            AllToolsCommand::class,
        ];
```

`sca:all` needs no change: `AllToolsCommand::executeSubcommands()` iterates
`$this->getApplication()->all('sca')`, so it picks up anything registered in the `sca` namespace. It
forwards only those options your command actually declares, so a tool without `-f` is safe.

BAM! It's done! Now you can call your tool from command-line
```bash
src/bin/coding-standard sca:supercool # from your dev environment
vendor/bin/coding-standard sca:supercool # if installed as a library
```

### Test it

Both classes get a unit test under
`tests/Unit/CommandLine/StaticCodeAnalysis/SuperCoolTool/`. The command test extends the base test
matching its base class — `AbstractToolCommandTest`, `TargetableToolsCommandTest` or
`FixingToolCommandTest` — which already covers running, skipping and exception wrapping; you supply
`$terminalCommandName`, `$terminalCommandType`, `$exclusionToken` and `$allowedFileEndings` in
`setUp()`. `JSStyleLint` and `PHPStan` are the closest pairs to copy from.
