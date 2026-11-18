# Adding new information to your TerminalCommand

For information about adding your own TerminalCommand please refer to the [tutorial](HowToAddANewTool.md).

So you want to [implement a TerminalCommand](HowToAddANewTool.md) but sadly some
information you need to execute compile() in a meaningful way is not
supplied by the coding-standard yet.

> Check the aspect table in [the tutorial](HowToAddANewTool.md#implement-your-terminalcommand)
> first — Target, Fix, Exclusion, Extension, Verbose, Multiprocess and PhpVersion already exist.

To fix that you need to do the following.

* Add a TerminalCommand interface
* Add a TerminalCommandDecorator
* Register your TerminalCommandDecorator — in **two** places
* Add a Trait

Let's say you want to add the information 'SuperCool' so it can be used
while compiling a TerminalCommand. All three classes go into one new sub-namespace of
`Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand`, which is how
every existing aspect is laid out.

## The TerminalCommand interface

First create an interface in a sub-namespace of
`Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand`
and name it appropriately.

Let's call it `SuperInformedTerminalCommand.php`
in `Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation`.
This interface will tell a TerminalCommand what methods it needs to implement
to be super informed.

```php
<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation;

use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommand;

interface SuperInformedTerminalCommand extends TerminalCommand
{
    /**
     * Lets the command know what's up.
     */
    public function addInformation(string $information): void;
}
```

Well that was easy. Let's continue with the decorator.

## The TerminalCommandDecorator

The TerminalCommandDecorator will actually inject your super cool information into
TerminalCommands. Place your new TerminalCommandDecorator in
`Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation`
and call it `SuperCoolInformationDecorator.php`.

`TerminalCommandDecorator` is an **abstract class**, not an interface. It already implements
`getSubscribedEvents()` as `final`, binding `decorate` to
`TerminalCommandDecorator::EVENT_DECORATE_TERMINAL_COMMAND` at priority 50, so there is nothing
for you to subscribe by hand. All you owe it is `decorate()`.

```php
<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation;

use Override;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\DecorateEvent;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\TerminalCommandDecorator;

class SuperCoolInformationDecorator extends TerminalCommandDecorator
{
    /**
     * This method decorates the TerminalCommand contained in the generic event.
     * It will read information from the surrounding infrastructure or the command input.
     *
     * {@inheritDoc}
     */
    #[Override]
    public function decorate(DecorateEvent $event): void
    {
        // Check if the TerminalCommand wants to be super informed. Not
        // only is it rude to tell people things they don't want to hear.
        // It will also crash the coding-standard.
        $terminalCommand = $event->getTerminalCommand();

        if (!$terminalCommand instanceof SuperInformedTerminalCommand) {
            return;
        }

        // Now we actually inject the information. As we just implemented the
        // interface we can be sure that this method exists.
        $terminalCommand->addInformation('SuperCool');
    }
}
```

The event also carries the command input and output, the exclusion list token and the allowed file
endings — see `DecorateEvent` and `CodingStandardCommandEvent` for the getters, which is how
`TargetDecorator` gets at `-t` and `-a`.

## Register the TerminalCommandDecorator

So you wrote your SuperCoolInformationDecorator. Nice! But you need to tell
the coding-standard about its existence. Open
`Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\EventDispatcherFactory`.

```php
class EventDispatcherFactory
{
    /** @var array<string> */
    private const array SUBSCRIBERS
        = [
            GitCommandPreconditionChecker::class,
            TerminalCommandPreconditionChecker::class,
            ExclusionDecorator::class,
            FileExtensionDecorator::class,
            FixDecorator::class,
            TargetDecorator::class,
            VerboseDecorator::class,
            MultiprocessDecorator::class,
            MinimalVersionDecorator::class,
            VersionDecorator::class,
            SuperCoolInformationDecorator::class, // Add your decorator to the list
        ];

    // [...]
}
```

As with the command list, there is a **second copy** of it in
`Zooroyal\CodingStandard\Tests\Unit\CommandLine\ApplicationLifeCycle\EventDispatcherFactoryTest::$subscribers`,
and it is not generated from the first. Forgetting it turns the build red.

## The Trait

Well... strictly speaking the trait is not needed for everything to work but
to make the life of other developers easier you should definitely consider it.

The trait should reside next to your other code so let's add it right there in
`Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation`.
As names are important &ndash; to us; Coding-Standard not so much &ndash; call your new
trait `SuperInformedTrait.php`

```php
<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\SuperInformation;

trait SuperInformedTrait
{
    // Choose a field to store the information it receives from your decorator.
    // Don't forget to give it some default value.
    // As traits are interpreter supported copy and paste, please do not try to
    // interact with anything outside of your trait. Please don't.
    protected string $information = '';

    // Now add a sensible default implementation for your information
    // gathering method. The signature is forced by your interface so don't
    // look at me if you think it's wonky ^^
    /**
     * Receive super important information.
     */
    final public function addInformation(string $information): void
    {
        $this->information = $information;
    }
}
```
