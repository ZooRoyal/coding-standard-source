<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\ObjectsCallisthenics\Standards\ZooRoyal\Helper;

use PHP_CodeSniffer\Files\File;
use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper\ClassAnalyzer;
use Zooroyal\CodingStandard\Tests\Unit\Sniffs\ObjectsCallisthenics\FileFactory;

final class ClassAnalyzerTest extends TestCase
{
    /**
     * @var int
     */
    private const CLASS_POSITION = 3;

    private File $file;

    protected function setUp(): void
    {
        $fileFactory = new FileFactory();
        $this->file = $fileFactory->createFile(__DIR__ . '/ClassAnalyzerSource/SomeFile.php.inc');
    }

    public function testMethodCount(): void
    {
        $this->assertSame(2, ClassAnalyzer::getClassMethodCount($this->file, self::CLASS_POSITION));
    }

    public function testProperties(): void
    {
        $properties = ClassAnalyzer::getClassProperties($this->file, self::CLASS_POSITION);

        $this->assertCount(1, $properties);
    }

    public function testPropertyCount(): void
    {
        $this->assertSame(1, ClassAnalyzer::getClassPropertiesCount($this->file, self::CLASS_POSITION));
    }
}
