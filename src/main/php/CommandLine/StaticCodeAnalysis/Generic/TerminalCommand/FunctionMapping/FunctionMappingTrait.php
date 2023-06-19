<?php

declare(strict_types=1);

namespace Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\Generic\TerminalCommand\FunctionMapping;

trait FunctionMappingTrait
{
    public const TOOL_FUNCTIONS_FILE_MAPPING
        = [
            'hamcrest/hamcrest-php' => ['/hamcrest/Hamcrest.php'],
            'sebastianknott/hamcrest-object-accessor' => ['/src/functions.php'],
            'mockery/mockery' => ['/library/helpers.php'],
            'deployer/deployer' => ['/src/functions.php'],
        ];
}
