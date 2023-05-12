<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe;

use PHPUnit\Framework\TestCase;
use RuntimeException as GeneralRuntimeException;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Safe\RuntimeException;

class RuntimeExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function instanceOfRuntimeException(): void
    {
        $subject = new RuntimeException('foo', 1);
        self::assertInstanceOf(GeneralRuntimeException::class, $subject);
    }
}
