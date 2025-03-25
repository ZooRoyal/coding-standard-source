<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints;

use Mockery;
use PHP_CodeSniffer\Files\File;
use PHPUnit\Framework\TestCase;
use SlevomatCodingStandard\Helpers\UseStatementHelper;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints\LimitUseStatementSniff;

class LimitUseStatementSniffTest extends TestCase
{
    private LimitUseStatementSniff $subject;

    protected function setUp(): void
    {
        $this->subject = new LimitUseStatementSniff();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider         processHappyPathDataProvider
     * @runInSeparateProcess
     * @preserveGlobalState  false
     */
    public function processHappyPath(int $numberOfElements): void
    {
        $mockedFile = mock(File::class);
        $mockedUserStatementHelper = mock('overload:' . UseStatementHelper::class);

        $mockedResult = array_pad([], $numberOfElements, 'a');

        $mockedUserStatementHelper->expects()->getFileUseStatements($mockedFile)
            ->andReturn([11 => $mockedResult]);

        $mockedFile->shouldNotReceive('addError');

        $this->subject->process($mockedFile, 10);
    }

    /** @return array<string,array<string,int>> */
    public function processHappyPathDataProvider(): array
    {
        return [
            'No uses' => ['numberOfElements' => 0],
            'Some uses' => ['numberOfElements' => 10],
        ];
    }

    /**
     * @test
     * @dataProvider         processReportsErrorOnTooManyUsesDataProvider
     * @runInSeparateProcess
     * @preserveGlobalState  false
     */
    public function processReportsErrorOnTooManyUses(
        int $numberOfElements,
        int $maximumUseStatements,
    ): void {
        $forgedStackPointer = 10;
        $mockedFile = mock(File::class);
        $mockedUserStatementHelper = mock('overload:' . UseStatementHelper::class);

        $mockedResult = array_pad([], $numberOfElements, 'a');

        $mockedUserStatementHelper->expects()->getFileUseStatements($mockedFile)
            ->andReturn([11 => $mockedResult]);

        $mockedFile->expects()->addError(
            'Too many use statements. Maximum allowed is %s, but found %s.',
            $forgedStackPointer,
            'TooManyUseStatements',
            [$maximumUseStatements, $numberOfElements]
        );

        $this->subject->maximumUseStatements = $maximumUseStatements;
        $this->subject->process($mockedFile, $forgedStackPointer);
    }

    /** @return array<string,array<string,int>> */
    public function processReportsErrorOnTooManyUsesDataProvider(): array
    {
        return [
            'Too many uses' => ['numberOfElements' => 20, 'maximumUseStatements' => 15 ],
            'Exactly over the limit' => ['numberOfElements' => 16, 'maximumUseStatements' => 15],
            'Different Limit' => ['numberOfElements' => 4, 'maximumUseStatements' => 3],
        ];
    }
}
