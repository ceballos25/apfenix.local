<?php

/**
 * Logger simple para el informe gerencial (cron).
 */
class InformeLogger
{
    private static function logPath(): string
    {
        $dir = ROOT_PATH . '/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/informe-gerencial.log';
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    private static function write(string $level, string $message): void
    {
        file_put_contents(
            self::logPath(),
            '[' . date('Y-m-d H:i:s') . "] [{$level}] {$message}" . PHP_EOL,
            FILE_APPEND
        );
    }
}
