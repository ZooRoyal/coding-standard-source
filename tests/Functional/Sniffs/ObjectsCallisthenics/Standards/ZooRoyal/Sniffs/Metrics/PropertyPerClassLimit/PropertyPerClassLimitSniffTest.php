<?php

declare(strict_types=1);

// phpcs:disable
namespace Zooroyal\CodingStandard\Tests\Functional\Sniffs\ObjectsCallisthenics\Standards\ZooRoyal\Sniffs\Metrics\PropertyPerClassLimit;
// phpcs:enable

use Composer\Autoload\ClassLoader;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Process\Process;

class PropertyPerClassLimitSniffTest extends TestCase
{
    private const string SNIFF_NAME = 'ZooRoyal.Metrics.PropertyPerClassLimit';

    private const string FIXTURE_DIRECTORY_WRONG
        = 'tests/Functional/Sniffs/ObjectsCallisthenics/Standards/ZooRoyal/Sniffs/Metrics/PropertyPerClassLimit/wrong';

    private const string FIXTURE_DIRECTORY_CORRECT
        = 'tests/Functional/Sniffs/ObjectsCallisthenics/Standards/ZooRoyal/Sniffs/Metrics/PropertyPerClassLimit/correct';

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
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY_CORRECT;
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
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY_WRONG;
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->run();
        $subject->wait();
        $output = $subject->getOutput();
        self::assertMatchesRegularExpression('/"class" has too many properties: 26\. Can be up to 25/', $output);
    }
}
