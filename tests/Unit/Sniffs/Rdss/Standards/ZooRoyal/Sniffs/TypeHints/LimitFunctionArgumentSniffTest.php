<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints;

use Mockery;
use PHP_CodeSniffer\Files\File;
use PHPUnit\Framework\TestCase;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\TypeHints\LimitFunctionArgumentSniff;

class LimitFunctionArgumentSniffTest extends TestCase
{
    private LimitFunctionArgumentSniff $subject;

    protected function setUp(): void
    {
        $this->subject = new LimitFunctionArgumentSniff();
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
        $forgedStackPointer = 111;
        $mockedFile = mock(File::class);
        $mockedFunctionHelper = mock('overload:' . FunctionHelper::class);

        $mockedResult = array_pad([], $numberOfElements, 'a');

        $mockedFunctionHelper->expects()->getParametersNames($mockedFile, $forgedStackPointer)
            ->andReturn($mockedResult);

        $mockedFile->shouldNotReceive('addError');

        $this->subject->process($mockedFile, $forgedStackPointer);
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
     * @dataProvider         processReportsErrorOnTooManyArgumentsDataProvider
     * @runInSeparateProcess
     * @preserveGlobalState  false
     */
    public function processReportsErrorOnTooManyArguments(
        int $numberOfElements,
        int $maximumArguments,
    ): void {
        $forgedStackPointer = 111;
        $mockedFile = mock(File::class);
        $mockedFunctionHelper = mock('overload:' . FunctionHelper::class);

        $mockedResult = array_pad([], $numberOfElements, 'a');

        $mockedFunctionHelper->expects()->getParametersNames($mockedFile, $forgedStackPointer)
            ->andReturn($mockedResult);

        $mockedFile->expects()->addError(
            'Method has too many parameters. Maximum allowed is %s, but found %s.',
            $forgedStackPointer,
            'TooManyArguments',
            [$maximumArguments, $numberOfElements]
        );

        $this->subject->maximumArguments = $maximumArguments;
        $this->subject->process($mockedFile, $forgedStackPointer);
    }

    /** @return array<string,array<string,int>> */
    public function processReportsErrorOnTooManyArgumentsDataProvider(): array
    {
        return [
            'Too many arguments' => ['numberOfElements' => 20, 'maximumArguments' => 10],
            'Exactly over maximum arguments' => ['numberOfElements' => 11, 'maximumArguments' => 10],
            'Different limit' => ['numberOfElements' => 4, 'maximumArguments' => 3],
        ];
    }
}
