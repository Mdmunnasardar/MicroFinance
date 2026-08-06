<?php

declare(strict_types=1);

return [
    'name' => 'MicroFinance API',
    'environment' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOL),
    'base_path' => dirname(__DIR__, 2),
    'storage_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage',
];
