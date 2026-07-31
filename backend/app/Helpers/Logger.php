<?php

declare(strict_types=1);

namespace App\Helpers;

final class Logger
{
    public static function error(\Throwable|string $error): void
    {
        $message = $error instanceof \Throwable
            ? sprintf("[%s] %s in %s:%d\n%s", get_class($error), $error->getMessage(), $error->getFile(), $error->getLine(), $error->getTraceAsString())
            : $error;

        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($directory . DIRECTORY_SEPARATOR . 'api.log', $line, FILE_APPEND | LOCK_EX);
    }
}
