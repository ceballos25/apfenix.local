<?php

require_once __DIR__ . '/../controllers/apiRequest.controller.php';

class CouponHelper
{
    const CODE = 'APF15';
    const DISCOUNT_PERCENT = 15;
    const EXPIRES = '2026-06-22 23:59:59';
    const TIMEZONE = 'America/Bogota';
    const PROMO_UNIT_QTY = 20;
    const PROMO_UNIT_PRICE = 900;

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

    public static function isValidCode(?string $code): bool
    {
        if (!self::isActive() || $code === null || trim($code) === '') {
            return false;
        }

        return strtoupper(trim($code)) === self::CODE;
    }

    public static function unitPrice(int $quantity, float $rafflePrice): int
    {
        return $quantity >= self::PROMO_UNIT_QTY
            ? self::PROMO_UNIT_PRICE
            : (int) $rafflePrice;
    }

    public static function calculateAmount(int $quantity, float $rafflePrice, ?string $couponCode = null): array
    {
        $subtotal = $quantity * self::unitPrice($quantity, $rafflePrice);
        $discount = 0;
        $applied = false;

        if (self::isValidCode($couponCode)) {
            $discount = (int) round($subtotal * self::DISCOUNT_PERCENT / 100);
            $applied = true;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'applied' => $applied,
        ];
    }

    public static function getRafflePrice(int $idRaffle): ?float
    {
        $res = ApiRequest::get('raffles', [
            'linkTo' => 'id_raffle',
            'equalTo' => (string) $idRaffle,
            'select' => 'price_raffle',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return null;
        }

        $raffle = is_array($res->results) ? $res->results[0] : $res->results;

        return isset($raffle->price_raffle) ? (float) $raffle->price_raffle : null;
    }

    public static function resolveOrderAmount(int $idRaffle, int $quantity, ?string $couponCode = null): array
    {
        if ($quantity < 3) {
            return [
                'success' => false,
                'message' => 'La compra mínima es de 3 números',
            ];
        }

        return self::resolveSaleAmount($idRaffle, $quantity, $couponCode);
    }

    public static function resolveSaleAmount(int $idRaffle, int $quantity, ?string $couponCode = null): array
    {
        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Cantidad inválida',
            ];
        }

        $price = self::getRafflePrice($idRaffle);

        if ($price === null) {
            return [
                'success' => false,
                'message' => 'No se pudo validar el sorteo',
            ];
        }

        if (!self::isValidCode($couponCode) && $couponCode !== null && trim($couponCode) !== '') {
            return [
                'success' => false,
                'message' => 'Cupón inválido o expirado',
            ];
        }

        if (($couponCode === null || trim($couponCode) === '') && self::isActive()) {
            $couponCode = self::CODE;
        }

        $calc = self::calculateAmount($quantity, $price, $couponCode);

        return [
            'success' => true,
            'amount' => $calc['total'],
            'subtotal' => $calc['subtotal'],
            'discount' => $calc['discount'],
            'coupon_applied' => $calc['applied'],
        ];
    }
}
