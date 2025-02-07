<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Functional\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use Composer\Autoload\ClassLoader;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Process\Process;

class CheckSafeFunctionUsageSniffTest extends TestCase
{
    private const string SNIFF_NAME = 'ZooRoyal.Safe.CheckSafeFunctionUsage';
    private const string FIXTURE_DIRECTORY = 'tests/Functional/Sniffs/Rdss/Standards/ZooRoyal/Sniffs/Safe/fixtures/';

    private static string $vendorDir;
    /** @var array<string> */
    private array $commandPrefix = [];

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
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY . 'NothingToDo.php';
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->mustRun();
        $subject->wait();

        self::assertSame(0, $subject->getExitCode());
    }

    /**
     * @test
     * @medium
     */
    public function itShouldReportErrorsForNotImportedSafeMethods(): void
    {
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY . 'Alarm.php';
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->run();
        $subject->wait();
        $exitCode = $subject->getExitCode();
        $output = $subject->getOutput();

        self::assertNotSame(0, $exitCode);
        self::assertMatchesRegularExpression('/FOUND 1 ERROR AFFECTING 1 LINE/', $output);
        self::assertMatchesRegularExpression(
            '/ZooRoyal\.Safe\.CheckSafeFunctionUsage\.FunctionNotImported/',
            $output,
        );
        self::assertMatchesRegularExpression('/Function.*\'scandir\'.*is.*no.*imported.*from.*Safe!.*Add.*\'use/ms', $output);
        self::assertMatchesRegularExpression('/function.*Safe\\\\scandir;\'.*to.*your.*uses\./ms', $output);
    }
}
