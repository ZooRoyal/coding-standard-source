<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\ObjectsCallisthenics\Standards\ZooRoyal\Helper;

use PHPUnit\Framework\TestCase;
use Zooroyal\CodingStandard\Sniffs\ObjectCalisthenics\Standards\ZooRoyal\Helper\NamingHelper;
use Zooroyal\CodingStandard\Tests\Unit\Sniffs\ObjectsCallisthenics\FileFactory;

final class NamingHelperTest extends TestCase
{
    /** @var int */
    private const CLASS_POSITION = 4;

    /** @var int */
    private const CONSTANT_POSITION = 12;

    /** @var int */
    private const PROPERTY_POSITION = 25;

    private FileFactory $fileFactory;

    protected function setUp(): void
    {
        $this->fileFactory = new FileFactory();
    }

    public function test(): void
    {
        $file = $this->fileFactory->createFile(__DIR__ . '/NamingSource/SomeFile.php.inc');

        $name = NamingHelper::getElementName($file, self::CLASS_POSITION);
        $this->assertSame('SomeClass', $name);

        $name = NamingHelper::getElementName($file, self::CONSTANT_POSITION);
        $this->assertSame('SOME_CONSTANT', $name);

        $name = NamingHelper::getElementName($file, self::PROPERTY_POSITION);
        $this->assertSame('someProperty', $name);
    }
}
