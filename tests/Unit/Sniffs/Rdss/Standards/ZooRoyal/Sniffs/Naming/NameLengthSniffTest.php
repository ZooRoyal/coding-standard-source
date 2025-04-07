<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\Tests\Unit\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Naming;

use Mockery;
use PHP_CodeSniffer\Files\File;
use PHPUnit\Framework\TestCase;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Zooroyal\CodingStandard\Sniffs\Rdss\Standards\ZooRoyal\Sniffs\Naming\NameLengthSniff;

class NameLengthSniffTest extends TestCase
{
    private NameLengthSniff $subject;

    protected function setUp(): void
    {
        $this->subject = new NameLengthSniff();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     * @dataProvider processHappyPathDataProvider
     */
    public function processHappyPath(string $name): void
    {
        $mockedFile = mock(File::class);
        $forgedStackPointer = 0;
        $forgedTokens = [ $forgedStackPointer => ['content' => $name]];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedFile->shouldNotReceive('addError');

        $this->subject->process($mockedFile, $forgedStackPointer);
    }

    /** @return array<string,array<string,string>> */
    public function processHappyPathDataProvider(): array
    {
        return [
                'Good Name' => ['name' => 'aaaaaaa'],
                'Almost too short name' => ['name' => 'aaa'],
                'Almost too long name' => ['name' => str_pad('a', 70, 'a')],
                'i' => ['name' => '$i'],
            ];
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState  false
     * @dataProvider         processReportsErrorIfClassysAreBadDataProvider
     */
    public function processReportsErrorIfClassysAreBad(
        string $token,
        string $name,
        string $errorMessage,
        string $code,
        int $length,
    ): void {
        $mockedFile = mock(File::class);
        $mockedClassHelper = mock('overload:' . ClassHelper::class);

        $forgedStackPointer = 0;
        $forgedTokens = [ $forgedStackPointer => ['content' => $token]];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedClassHelper->expects()->getName($mockedFile, $forgedStackPointer)->andReturn($name);

        $mockedFile->expects()->addError(
            $errorMessage,
            $forgedStackPointer,
            $code,
            [$name, $length]
        );

        $this->subject->process($mockedFile, $forgedStackPointer);
    }


    /** @return array<string,array<string,int|string>> */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength
    public function processReportsErrorIfClassysAreBadDataProvider(): array
    {
        return [
            'class too short' => [
                'token' => 'class',
                'name' => 'aa',
                'errorMessage' => 'Name "%s" is less than %s characters long',
                'code' => 'NameTooShort',
                'length' => 3,
            ],
            'class too long' => [
                'token' => 'class',
                'name' => str_pad('', 71, 'a'),
                'errorMessage' => 'Name "%s" is greater than %s characters long',
                'code' => 'NameTooLong',
                'length' => 70,
            ],
            'trait too short' => [
                'token' => 'trait',
                'name' => 'aa',
                'errorMessage' => 'Name "%s" is less than %s characters long',
                'code' => 'NameTooShort',
                'length' => 3,
            ],
            'trait too long' => [
                'token' => 'trait',
                'name' => str_pad('$', 71, 'a'),
                'errorMessage' => 'Name "%s" is greater than %s characters long',
                'code' => 'NameTooLong',
                'length' => 70,
            ],
            'interface too short' => [
                'token' => 'interface',
                'name' => 'aa',
                'errorMessage' => 'Name "%s" is less than %s characters long',
                'code' => 'NameTooShort',
                'length' => 3,
            ],
            'interface too long' => [
                'token' => 'interface',
                'name' => str_pad('$', 71, 'a'),
                'errorMessage' => 'Name "%s" is greater than %s characters long',
                'code' => 'NameTooLong',
                'length' => 70,
            ],
        ];
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState  false
     * @dataProvider         processReportsErrorIfFunctionIsBadDataProvider
     */
    public function processReportsErrorIfFunctionIsBad(
        string $functionName,
        string $errorMessage,
        string $code,
        int $length,
    ): void {
        $mockedFile = Mockery::mock(File::class);
        $mockedFunctionHelper = mock('overload:' . FunctionHelper::class);

        $forgedStackPointer = 0;
        $forgedTokens = [ $forgedStackPointer => ['content' => 'function']];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedFunctionHelper->expects()->getName($mockedFile, $forgedStackPointer)->andReturn($functionName);
        $mockedFile->expects()->addError(
            $errorMessage,
            $forgedStackPointer,
            $code,
            [$functionName, $length]
        );

        $this->subject->process($mockedFile, $forgedStackPointer);
    }

    /** @return array<string,array<string,int|string>> */
    public function processReportsErrorIfFunctionIsBadDataProvider(): array
    {
        return [
            'too short' => [
                'functionName' => 'a',
                'errorMessage' => 'Name "%s" is less than %s characters long',
                'code' => 'NameTooShort',
                'length' => 3,
            ],
            'too long' => [
                'functionName' => str_pad('a', 71, 'a'),
                'errorMessage' => 'Name "%s" is greater than %s characters long',
                'code' => 'NameTooLong',
                'length' => 70,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider processReportsErrorIfVariableIsBadDataProvider
     */
    public function processReportsErrorIfVariableIsBad(
        string $variable,
        string $errorMessage,
        string $code,
        int $length,
    ): void {
        $mockedFile = mock(File::class);
        $forgedStackPointer = 0;
        $forgedTokens = [ $forgedStackPointer => ['content' => $variable]];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedFile->expects()->addError(
            $errorMessage,
            $forgedStackPointer,
            $code,
            [ltrim($variable, '$'), $length]
        );

        $this->subject->process($mockedFile, $forgedStackPointer);
    }

    /** @return array<string,array<string,int|string>> */
    public function processReportsErrorIfVariableIsBadDataProvider(): array
    {
        return [
            'too short' => [
                'variable' => '$a',
                'errorMessage' => 'Name "%s" is less than %s characters long',
                'code' => 'NameTooShort',
                'length' => 3,
            ],
            'too long' => [
                'variable' => str_pad('$', 72, 'a'),
                'errorMessage' => 'Name "%s" is greater than %s characters long',
                'code' => 'NameTooLong',
                'length' => 70,
            ],
        ];
    }

    /**
     * @test
     */
    public function registerReturnsExpectedTokens(): void
    {
        $expected = [T_VARIABLE, T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT, T_PROPERTY];
        $this->assertEquals($expected, $this->subject->register());
    }

    /**
     * @test
     */
    public function processWithConfiguredMaximum(): void
    {
        $mockedFile = mock(File::class);
        $forgedStackPointer = 0;
        $variable = str_pad('$', 40, 'a');
        $forgedTokens = [ $forgedStackPointer => ['content' => $variable]];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedFile->expects()->addError(
            'Name "%s" is greater than %s characters long',
            $forgedStackPointer,
            'NameTooLong',
            [ltrim($variable, '$'), 30]
        );

        $this->subject->maximumLength = 30;
        $this->subject->process($mockedFile, $forgedStackPointer);
    }
    /**
     * @test
     */
    public function processWithConfiguredMinimum(): void
    {
        $mockedFile = mock(File::class);
        $forgedStackPointer = 0;
        $variable = str_pad('$', 29, 'a');
        $forgedTokens = [ $forgedStackPointer => ['content' => $variable]];

        $mockedFile->expects()->getTokens()->andReturn($forgedTokens);
        $mockedFile->expects()->addError(
            'Name "%s" is less than %s characters long',
            $forgedStackPointer,
            'NameTooShort',
            [ltrim($variable, '$'), 30]
        );

        $this->subject->minimumLength = 30;
        $this->subject->process($mockedFile, $forgedStackPointer);
    }
}
