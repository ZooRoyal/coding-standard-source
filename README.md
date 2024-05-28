[![CI](https://github.com/ZooRoyal/coding-standard-source/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ZooRoyal/coding-standard-source/actions/workflows/continuous-integration.yml)
[![Docker Build](https://github.com/ZooRoyal/coding-standard-source/actions/workflows/docker-build.yml/badge.svg)](https://github.com/ZooRoyal/coding-standard-source/actions/workflows/docker-build.yml)
[![Packagist Release](https://img.shields.io/packagist/v/ZooRoyal/coding-standard-source.svg?longCache=true)](https://packagist.org/packages/zooroyal/coding-standard-source)
[![License](https://img.shields.io/packagist/l/ZooRoyal/coding-standard.svg?longCache=true)](/blob/master/LICENSE)

# ZooRoyal Coding Standard Source

---

This repository holds the necessary sources to use and build the ZooRoyal
Coding Standard.

It incorporates
* [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer)
  and its configuration
* [PHP Mess Detector](https://github.com/phpmd/phpmd) and its configuration
* [PHP Copy Paste Detector](https://github.com/sebastianbergmann/phpcpd)
* [PHP-Parallel-Lint](https://github.com/JakubOnderka/PHP-Parallel-Lint)
* [PHPStan - PHP Static Analysis Tool](https://github.com/phpstan/phpstan)
  and its configuration
* [ES-LINT](https://github.com/eslint/eslint) and its configuration
* [STYLE-LINT](https://github.com/stylelint/stylelint) and its configuration

In addition, there is a PHP script in src/bin to be used by a
continuous integration tool of your choice. It searches your source code to
find files to check with its static code analysis tools. Information about
its usage can be found by calling it with -h option.

## Installation (as intended)

### Docker

The ZooRoyal Coding Standard is designed to be used as a docker isolated
application. Therefore, no installation is needed if you have a docker
daemon available on your system.

### Composer

If ...
* ... you don't feel comfortable with the docker user experience ...
* ... want to bind your source code to a certain version of the ZooRoyal
  Coding Standard ...

... you find a ready-made composer package at https://github.com/ZooRoyal/coding-standard

```bash
composer require --dev "zooroyal/coding-standard"
```

It will still require you to have a docker daemon available on your system.

The ZooRoyal Coding Standard application will be available under
`vendor/bin/coding-standard`.

## Install from source (not recommended)

If all else fails, you can install the ZooRoyal Coding Standard from this source
package. **Please be aware that this is not recommended**. There will be
tons of dependencies the rest of your project will have to comply with. They
will be very specific and change as we see fit.

#### Standalone

If you want to use the ZooRoyal Coding Standard as a standalone application, you
can use the following commands:

```bash
git clone git@github.com:ZooRoyal/coding-standard-source.git
cd coding-standard-source
composer install
```

The ZooRoyal Coding Standard application will be available under
`src/bin/coding-standard`.

#### As composer dependency

To use the ZooRoyal Coding Standard as a composer dependency, you can
require it via composer but this alone won't be enough. You will have to install
the necessary tools as well. For this to happen, you can use the following
process:

```bash
composer require --dev "zooroyal/coding-standard-source"
```

Now you need to add the following lines to your composer.json:

```json
{
    [...]
    "extra": {
        [...]
        "bamarni-bin": {
            "bin-links": true,
            "forward-command": true
        }
    }
    [...]
}
```

The final step is to install the necessary tools. This can be done by
running the following command:

```bash
cp -r vendor/zooroyal/coding-standard-source/vendor-bin .
composer update
# If you need Javascript support ...
npm install --save-dev vendor/zooroyal/coding-standard-source
```

This will install the following tools in several directories under `vendor-bin`:

* [PHPStan](https://phpstan.org/) ([composer.json](./vendor-bin/phpstan/composer.json))
* [PHP-Parallel-Lint](https://github.com/php-parallel-lint/PHP-Parallel-Lint)
  ([composer.json](./vendor-bin/php-parallel-lint/composer.json))
* [PHPCPD](https://packagist.org/packages/sebastian/phpcpd)
  ([composer.json](./vendor-bin/phpcpd/composer.json))
* [PHPMD](https://phpmd.org/)
  ([composer.json](./vendor-bin/phpmd/composer.json))

This tools will be installed in `node_modules`:
* [EsLint](https://eslint.org/)
  ([package.json](./package.json))
* [StyleLint](https://stylelint.io/)
  ([package.json](./package.json))

The ZooRoyal Coding Standard application will be available under
`vendor/bin/coding-standard`.

## Usage ZooRoyal Coding Standard

Please keep in mind, that ZooRoyal Coding Standard can only check source
code which is a **git repository** as well as a **composer project**

### Using the docker image directly

If you want to use the docker image directly, you can use the following command:

```bash
docker run --rm -t -v $(pwd):/app ghcr.io/zooroyal/coding-standard-source:latest <parameter>
```

This will mount your current working directory as the root directory of the
source code you want to check.

For your convenience, you can create an alias for this command:
```bash
alias coding-standard="docker run --rm -t -v $(pwd):/app ghcr.io/zooroyal/coding-standard-source:latest"
```

To use a certain version of the coding-standard, just add the version tag to the image name:
```bash
docker run --rm -t -v $(pwd):/app ghcr.io/zooroyal/coding-standard-source:4.0.0 <parameter>
```

### Using one of the composer packages

The composer package will install a script in your `vendor/bin` folder of your composer project
or `src/bin`  of the standalone installation folder. Use them to run the
ZooRoyal Coding Standard.

The ZooRoyal Coding Standard application must be executed from the root of
your project.

### Using the ZooRoyal Coding Standard

Run the command to get usage instructions.
```bash
coding-standard
```
```
Available commands:
  find-files                Finds files for code style checks.
  help                      Displays help for a command
  list                      Lists commands
 checks
  checks:forbidden-changes  Checks for unwanted code changes.
 sca
  sca:all                   Run all static code analysis tools.
  sca:copy-paste-detect     Run PHP-CPD on PHP files.
  sca:eslint                Run ESLint on JS files.
  sca:mess-detect           Run PHP-MD on PHP files.
  sca:parallel-lint         Run Parallel-Lint on PHP files.
  sca:sniff                 Run PHP-CS on PHP files.
  sca:stylelint             Run StyleLint on Less files.
  sca:stan                  Run PHPStan on PHP files.
```

### Example `sca:all`

```bash
coding-standard sca:all -h
```
```
Usage:
  sca:all [options]

Options:
  -t, --target=TARGET      Finds Files which have changed since the current branch parted from the target
                           branch only. The Value has to be a commit-ish. [default: false]
  -a, --auto-target        Finds Files which have changed since the current branch parted from the parent
                           branch only. It tries to find the parent branch by automagic.
  -f, --fix                Runs tool to try to fix violations automagically.
  -p, --process-isolation  Runs all checks in separate processes. Slow but not as resource hungry.
  -h, --help               Display this help message
  -q, --quiet              Do not output any message
  -V, --version            Display this application version
      --ansi               Force ANSI output
      --no-ansi            Disable ANSI output
  -n, --no-interaction     Do not ask any interactive question
  -v|vv|vvv, --verbose     Increase the verbosity of messages: 1 for normal output, 2 for more verbose output
                           and 3 for debug

Help:
  This tool executes all static code analysis tools on files of this project. It ignores files which are in
  directories with a .dont<toolshortcut> file. Subdirectories are ignored too.
```

The all command forwards all applicable parameters to all implemented static code analysis tools.

```bash
coding-standard sca:all -a -f
```

This command for example tries to find the parent branch by automagic (-a) and tells all static code analysis
tools to fix found violations if they are able to.

```bash
coding-standard sca:all -t origin/master
```

This command computes the diff to the branch origin/master and searches for all violations in this files.

For examples just have a look an the .travis.yml

## Extend the Coding Standard

If you want to extend the ZooRoyal Coding Standard with your own tools there are two
tutorials available:

* [How to add a new static code analysis tool](doc/howto/HowToAddANewTool.md)
* [Adding new information to your TerminalCommand](doc/howto/HowToAddInformationSources.md)

## Integrating the Coding Standard into PHPStorm

If you want to integrate the ZooRoyal Coding Standard into PHPStorm, have a look
at the guides in the [doc/ideConfig](doc/ideConfig/IdeConfig.md) folder.
