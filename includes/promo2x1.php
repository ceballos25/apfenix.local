<?php

require_once __DIR__ . '/dinamica.php';

/**
 * Compatibilidad: la preventa reemplaza el 2×1.
 * Los controladores siguen llamando Promo2x1Helper::quantityDelivered().
 */
class Promo2x1Helper
{
    const MIN_QTY = DinamicaHelper::MIN_QTY;
    const EXPIRES = DinamicaHelper::PREVENTA_END;
    const TIMEZONE = DinamicaHelper::TIMEZONE;

    public static function isActive(): bool
    {
        return DinamicaHelper::isPreventaActive();
    }

    public static function getExpiresForJs(): string
    {
        return DinamicaHelper::getExpiresForJs();
    }

    public static function applies(int $paidQuantity): bool
    {
        return self::isActive() && $paidQuantity >= self::MIN_QTY;
    }

    public static function quantityDelivered(int $paidQuantity): int
    {
        require_once __DIR__ . '/preventa-qty.php';

        return max(
            DinamicaHelper::quantityDelivered($paidQuantity),
            apfenix_cantidad_entregada($paidQuantity)
        );
    }

    public static function bonusQuantity(int $paidQuantity): int
    {
        return DinamicaHelper::bonusQuantity($paidQuantity);
    }
}
