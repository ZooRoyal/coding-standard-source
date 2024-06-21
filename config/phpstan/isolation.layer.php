<?php

declare(strict_types=1);

use Zooroyal\CodingStandard\CommandLine\ApplicationLifeCycle\ContainerFactory;
use Zooroyal\CodingStandard\CommandLine\StaticCodeAnalysis\PHPStan\PHPStanConfigGenerator;

use function Safe\json_encode;

$autoloadFiles = [
    __DIR__ . '/../../../../autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        break;
    }
}
$config = ContainerFactory::getContainerInstance()
    ->get(PHPStanConfigGenerator::class)
    ->addDynamicConfigValues([]);

echo json_encode($config, JSON_THROW_ON_ERROR, 512);
