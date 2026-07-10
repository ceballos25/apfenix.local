<?php

/**
 * Promoción 2×1 — desde 50 números pagados, el cliente recibe el doble.
 */
class Promo2x1Helper
{
    const MIN_QTY = 50;
    const EXPIRES = '2026-07-16 23:59:59';
    const TIMEZONE = 'America/Bogota';

    public static function isActive(): bool
    {
        $tz = new DateTimeZone(self::TIMEZONE);
        $now = new DateTime('now', $tz);
        $expires = new DateTime(self::EXPIRES, $tz);

        return $now <= $expires;
    }

    public static function getExpiresForJs(): string
    {
        $dt = new DateTime(self::EXPIRES, new DateTimeZone(self::TIMEZONE));

        return $dt->format('c');
    }

    public static function applies(int $paidQuantity): bool
    {
        return self::isActive() && $paidQuantity >= self::MIN_QTY;
    }

    /** Números que recibe el cliente (pagados × 2 si aplica promo). */
    public static function quantityDelivered(int $paidQuantity): int
    {
        return self::applies($paidQuantity) ? $paidQuantity * 2 : $paidQuantity;
    }

    public static function bonusQuantity(int $paidQuantity): int
    {
        return self::quantityDelivered($paidQuantity) - $paidQuantity;
    }
}
