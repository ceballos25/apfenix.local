<?php

class SalesClosedHelper
{
    public static function isActive(): bool
    {
        $value = env('SALES_CLOSED', false);

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'si', 'on'], true);
    }

    public static function message(): string
    {
        $message = trim((string) env(
            'SALES_CLOSED_MESSAGE',
            'Las ventas están cerradas por el momento. ¡Gracias por participar!'
        ));

        return $message !== '' ? $message : 'Las ventas están cerradas por el momento. ¡Gracias por participar!';
    }

    public static function frontendConfig(): array
    {
        return [
            'activo' => self::isActive(),
            'mensaje' => self::message(),
        ];
    }
}
