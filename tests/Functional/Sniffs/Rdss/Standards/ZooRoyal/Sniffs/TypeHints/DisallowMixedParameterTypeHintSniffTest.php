<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints;

use Composer\Autoload\ClassLoader;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Process\Process;

class DisallowMixedParameterTypeHintSniffTest extends TestCase
{
    private const string SNIFF_NAME = 'Zooroyal.TypeHints.DisallowMixedParameterTypeHint';

    private const string FIXTURE_DIRECTORY
        = 'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/TypeHints/Fixtures/Parameter/';

    private static string $vendorDir;
    /** @var array<string> */
    private array $commandPrefix;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $reflection = new ReflectionClass(ClassLoader::class);
        self::$vendorDir = dirname($reflection->getFileName(), 2);

        require_once self::$vendorDir . '/squizlabs/php_codesniffer/autoload.php';
    }

    #[Override]
    protected function setUp(): void
    {
        $this->commandPrefix = [
            'vendor/bin/phpcs',
            '--sniffs=' . self::SNIFF_NAME,
            '--standard=ZooRoyal',
            '-s',
        ];
    }

    /**
     * @test
     * @medium
     */
    public function itShouldReportNoErrors(): void
    {
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY . 'FixtureNoMixedParameterTypeHints.php';
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->mustRun();
        $subject->wait();
        self::assertSame(0, $subject->getExitCode());
    }

    /**
     * @test
     * @medium
     */
    public function itShouldReportErrorsForExistingMixedTypes(): void
    {
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY . 'FixtureWithMixedParameterTypeHints.php';
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->run();
        $subject->wait();
        $output = $subject->getOutput();
        self::assertMatchesRegularExpression('/FOUND 5 ERRORS AFFECTING 3 LINES/', $output);
        self::assertMatchesRegularExpression(
            '/ZooRoyal.TypeHints.DisallowMixedParameterTypeHint.MixedParameterTypeHintUsed/',
            $output,
        );
        self::assertMatchesRegularExpression('/uses "mixed" type hint for parameter \$testArray/', $output);
        self::assertMatchesRegularExpression('/uses "mixed" type hint for parameter \$testString/', $output);
        self::assertMatchesRegularExpression('/uses "mixed" type hint for parameter \$testArray/', $output);
        self::assertMatchesRegularExpression('/closure\(\) uses "mixed" type hint for parameter \$data/', $output);
    }
}
