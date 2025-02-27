<?php

namespace Zooroyal\CodingStandard\Tests\Functional\Sniffs\ObjectsCallisthenics\Standards\ZooRoyal\Sniffs\Metrics\MethodPerClassLimit;

use Composer\Autoload\ClassLoader;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Process\Process;

class MethodPerClassLimitSniffTest extends TestCase
{

    private const string SNIFF_NAME = 'ZooRoyal.Metrics.MethodPerClassLimit';

    private const string FIXTURE_DIRECTORY
        = 'tests/Functional/Sniffs/ObjectsCallisthenics/Standards/ZooRoyal/Sniffs/Metrics/MethodPerClassLimit/wrong';

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
     * @covers \Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Sniffs\Metrics\MethodPerClassLimitSniff
     */
    public function itShouldReportNoErrors(): void
    {
        $this->commandPrefix[] = __FILE__;
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->mustRun();
        $subject->wait();
        self::assertSame(0, $subject->getExitCode());
    }

    /**
     * @test
     * @medium
     * @covers \Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Sniffs\Metrics\MethodPerClassLimitSniff
     */
    public function itShouldReportErrorsForExistingMixedTypes(): void
    {
        $this->commandPrefix[] = self::FIXTURE_DIRECTORY;
        $subject = new Process($this->commandPrefix, self::$vendorDir . '/../');
        $subject->run();
        $subject->wait();
        $output = $subject->getOutput();
        self::assertMatchesRegularExpression('/Trait has too many methods: 26\. Can be up to 25 methods\./', $output);
        self::assertMatchesRegularExpression('/Class has too many methods: 26\. Can be up to 25 methods\./', $output);
        self::assertMatchesRegularExpression('/Interface has too many methods: 26\. Can be up to 25 methods\./', $output);
    }
}
