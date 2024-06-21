<?php

declare(strict_types=1);

// phpcs:ignore ZooRoyal.Safe.CheckSafeFunctionUsage.FunctionNotImported
exec('php ' . __DIR__ . '/isolation.layer.php', $output);

$config = json_decode($output[0], true, 512, JSON_THROW_ON_ERROR);

return $config;
