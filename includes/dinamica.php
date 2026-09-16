<?php

/**
 * Dinámica vigente — precios, paquetes y preventa (web + venta manual).
 */
class DinamicaHelper
{
    const TIMEZONE = 'America/Bogota';
    const MIN_QTY = 3;
    const UNIT_PRICE = 9000;
    const PROMO_QTY = 25;
    const PROMO_PRICE = 8000;
    const BONUS_RATE = 0.30;

    const PREVENTA_START = '2026-09-16 00:00:00';
    const PREVENTA_END = '2026-09-25 23:59:59';

    const DRAW_DATE_LABEL = 'sábado 7 de noviembre';
    const DRAW_DATETIME = '2026-11-07 22:00:00';
    const LOTTERY = 'Lotería de Boyacá';
    const DRAW_WITH_LOTTERY = 'sábado 7 de noviembre con la de Boyacá';

    const PREMIO_MAYOR = 'MT-15 o $16 Millones';
    const PREMIO_INVERTIDO = '$7 Millones';
    const PREMIO_COPY = 'Por las 4 de Boyacá te llevas la MT-15 o $16 Millones. Y $7 Millones con el invertido';

    /** @var list<int> */
    const PACKAGES = [3, 5, 8, 10, 20, 25, 50];

    /** @var array<string, string> fecha ISO => etiqueta */
    const ANTICIPADOS = [
        '2026-10-03' => '3 de octubre',
        '2026-10-10' => '10 de octubre',
        '2026-10-17' => '17 de octubre',
        '2026-10-24' => '24 de octubre',
        '2026-10-31' => '31 de octubre',
    ];

    public static function proximoAnticipado(): ?string
    {
        $today = self::now()->format('Y-m-d');
        foreach (self::ANTICIPADOS as $iso => $label) {
            if ($iso >= $today) {
                return $label;
            }
        }

        return null;
    }

    public static function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }

    public static function parse(string $datetime): DateTime
    {
        return new DateTime($datetime, new DateTimeZone(self::TIMEZONE));
    }

    public static function formatCop(int $amount): string
    {
        return '$' . number_format($amount, 0, ',', '.');
    }

    public static function unitPrice(int $quantity): int
    {
        if (!self::isPreventaActive() && $quantity >= self::PROMO_QTY) {
            return self::PROMO_PRICE;
        }

        return self::UNIT_PRICE;
    }

    public static function subtotal(int $quantity): int
    {
        return $quantity * self::unitPrice($quantity);
    }

    public static function listPrice(int $quantity): int
    {
        return $quantity * self::UNIT_PRICE;
    }

    public static function savings(int $quantity): int
    {
        return max(0, self::listPrice($quantity) - self::subtotal($quantity));
    }

    /** upcoming | active | ended */
    public static function preventaPhase(): string
    {
        $now = self::now();
        if ($now < self::parse(self::PREVENTA_START)) {
            return 'upcoming';
        }
        if ($now <= self::parse(self::PREVENTA_END)) {
            return 'active';
        }

        return 'ended';
    }

    public static function isPreventaActive(): bool
    {
        return self::preventaPhase() === 'active';
    }

    public static function countdownTarget(): DateTime
    {
        $phase = self::preventaPhase();
        if ($phase === 'upcoming') {
            return self::parse(self::PREVENTA_START);
        }
        if ($phase === 'active') {
            return self::parse(self::PREVENTA_END);
        }

        return self::parse(self::DRAW_DATETIME);
    }

    public static function getExpiresForJs(): string
    {
        return self::countdownTarget()->format('c');
    }

    /**
     * En preventa: ~30% extra (mínimo 1 desde 3 números).
     * 3→+1, 5→+2, 8→+2, 10→+3, 20→+6, 25→+8, 50→+15.
     */
    public static function bonusQuantity(int $paidQuantity): int
    {
        if (!self::isPreventaActive() || $paidQuantity < self::MIN_QTY) {
            return 0;
        }

        return max(1, (int) round($paidQuantity * self::BONUS_RATE));
    }

    public static function quantityDelivered(int $paidQuantity): int
    {
        return $paidQuantity + self::bonusQuantity($paidQuantity);
    }

    public static function inferPaidFromTotal(float $total): int
    {
        $amount = (int) round($total);
        if ($amount <= 0) {
            return 0;
        }

        if (!self::isPreventaActive() && $amount % self::PROMO_PRICE === 0) {
            $qty = intdiv($amount, self::PROMO_PRICE);
            if ($qty >= self::PROMO_QTY) {
                return $qty;
            }
        }

        if ($amount % self::UNIT_PRICE === 0) {
            return intdiv($amount, self::UNIT_PRICE);
        }

        return max(self::MIN_QTY, (int) round($amount / self::UNIT_PRICE));
    }

    public static function packageMeta(int $qty): array
    {
        $map = [
            10 => ['badge' => 'Popular', 'slug' => 'popular'],
            25 => ['badge' => 'Promo $8 mil', 'slug' => 'ahorro'],
            50 => ['badge' => 'Mejor valor', 'slug' => 'mas-vendido'],
        ];

        return $map[$qty] ?? ['badge' => null, 'slug' => ''];
    }

    public static function packagesForUi(): array
    {
        $preventa = self::isPreventaActive();
        $out = [];

        foreach (self::PACKAGES as $qty) {
            $meta = self::packageMeta($qty);
            $bonus = $preventa ? self::bonusQuantity($qty) : 0;
            $showPreventa = $preventa;
            $showSaving = !$preventa && $qty >= self::PROMO_QTY;
            $badge = $preventa ? null : $meta['badge'];
            $slug = $preventa ? 'preventa' : $meta['slug'];
            $out[] = [
                'qty' => $qty,
                'id' => 'paq' . $qty,
                'price' => self::subtotal($qty),
                'list_price' => self::listPrice($qty),
                'savings' => self::savings($qty),
                'unit' => self::unitPrice($qty),
                'bonus' => $bonus,
                'delivered' => $qty + $bonus,
                'show_preventa' => $showPreventa,
                'show_saving' => $showSaving,
                'badge' => $badge,
                'slug' => $slug,
            ];
        }

        return $out;
    }

    public static function frontendConfig(): array
    {
        $phase = self::preventaPhase();

        return [
            'minimo' => self::MIN_QTY,
            'precio' => self::UNIT_PRICE,
            'precioPromo' => self::PROMO_PRICE,
            'desdePromo' => self::PROMO_QTY,
            'preventaActiva' => self::isPreventaActive(),
            'preventaFase' => $phase,
            'bonusRate' => self::BONUS_RATE,
            'expira' => self::getExpiresForJs(),
            'sorteo' => self::parse(self::DRAW_DATETIME)->format('c'),
        ];
    }

    public static function packagesSubtitle(): string
    {
        if (self::isPreventaActive()) {
            return 'Mínimo ' . self::MIN_QTY . ' · ' . self::formatCop(self::UNIT_PRICE) . ' c/u · extras en preventa';
        }

        return 'Mínimo ' . self::MIN_QTY . ' · ' . self::formatCop(self::UNIT_PRICE)
            . ' · desde ' . self::PROMO_QTY . ' a ' . self::formatCop(self::PROMO_PRICE);
    }

    public static function renderPriceHints(): string
    {
        if (self::isPreventaActive()) {
            return '<p class="cr-paquetes-hint__mobile mb-0">'
                . self::formatCop(self::UNIT_PRICE) . ' c/u · extras de preventa</p>'
                . '<div class="cr-paquetes-hint__desktop">'
                . '<p class="mb-0"><strong>Preventa:</strong> '
                . self::formatCop(self::UNIT_PRICE) . ' c/u · extras de regalo</p>'
                . '</div>';
        }

        return '<p class="cr-paquetes-hint__mobile mb-0">Desde ' . self::PROMO_QTY
            . ' números cada uno te sale a ' . self::formatCop(self::PROMO_PRICE) . '.</p>'
            . '<div class="cr-paquetes-hint__desktop">'
            . '<p class="mb-1"><strong>Precio:</strong> mínimo ' . self::MIN_QTY
            . ' números · ' . self::formatCop(self::UNIT_PRICE) . ' c/u</p>'
            . '<p class="mb-0"><strong>Promo volumen:</strong> desde ' . self::PROMO_QTY
            . ' números · ' . self::formatCop(self::PROMO_PRICE) . ' c/u</p>'
            . '</div>';
    }

    public static function renderPackageCards(bool $salesClosed = false, bool $compact = false): string
    {
        unset($compact);
        $html = '';
        $disabled = $salesClosed ? ' disabled' : '';

        foreach (self::packagesForUi() as $p) {
            $qty = (int) $p['qty'];
            $price = (int) $p['price'];
            $list = (int) $p['list_price'];
            $showSaving = !empty($p['show_saving']);
            $slug = (string) ($p['slug'] ?? '');
            $classes = ['paquete-card'];
            if ($slug !== '') {
                $classes[] = 'paquete-card--' . $slug;
            }
            if ($showSaving) {
                $classes[] = 'paquete-card--con-ahorro';
            }

            $badge = '';
            if (!empty($p['badge'])) {
                $badgeSlug = $slug !== '' ? $slug : 'popular';
                $badge = '<span class="badge-paquete badge-paquete--' . htmlspecialchars($badgeSlug, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars((string) $p['badge'], ENT_QUOTES, 'UTF-8') . '</span>';
            }

            $metaExtra = '';
            if (!empty($p['show_preventa'])) {
                $metaExtra = '<span class="paquete-unit-price">Recibes ' . (int) $p['delivered'] . '</span>';
            } elseif ($showSaving) {
                $metaExtra = '<span class="paquete-unit-price">' . self::formatCop((int) $p['unit']) . ' c/u</span>';
            }

            $antes = $showSaving
                ? '<span class="paquete-precio-antes">Antes <s>' . self::formatCop($list) . '</s></span>'
                : '';

            $html .= '<div class="col-12 col-md-4">'
                . '<input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="'
                . htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') . '" value="' . $qty . '"' . $disabled . '>'
                . '<label class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') . '" for="'
                . htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') . '">'
                . '<div class="paquete-card__body">'
                . '<div class="paquete-card__meta">'
                . $badge
                . '<span class="paquete-qty">' . $qty . ' <small>nums</small></span>'
                . $metaExtra
                . '</div>'
                . '<div class="paquete-precios">'
                . $antes
                . '<span class="paquete-precio-final">' . self::formatCop($price) . '</span>'
                . '</div></div></label></div>';
        }

        $html .= '<div class="col-12 col-md-4">'
            . '<input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paqCustom" value="custom"' . $disabled . '>'
            . '<label class="paquete-card paquete-card--personalizado custom" for="paqCustom">'
            . '<div class="paquete-card__body">'
            . '<div class="paquete-card__meta">'
            . '<span class="paquete-qty">Otro</span>'
            . '<span class="paquete-unit-price paquete-unit-price--muted">Tú eliges cuántos</span>'
            . '</div>'
            . '<div class="paquete-precios"><span class="paquete-precio-final paquete-precio-final--muted">→</span></div>'
            . '</div></label>'
            . '<input type="tel" id="cantidadManual" class="form-control form-control-sm text-center mt-1" min="'
            . self::MIN_QTY . '" placeholder="Mín. ' . self::MIN_QTY . ' nums" style="display:none;"'
            . ($salesClosed ? ' disabled' : '') . '>'
            . '</div>';

        return $html;
    }

    /**
     * Números bendecidos. Vacío = pendiente. Prefijo x = ya salió (tachado).
     * Ejemplo: '12345'  o  'x12345'
     */
    const BENDECIDOS_200 = [
        '1998', '1234', '0000', '1111', '9898', '7272', '5555', '8991', '0614', '7777',
    ];

    const BENDECIDOS_300 = [
        '0314', '1212', '9999', '2121', '0987', '2222', '6431', '0075', '9010', '0101',
        '8877', '5434', '8656', '4582', '3069', '8140', '6703', '2548', '1390', '4827',
    ];

    public static function renderBendecidosGrupo(array $numeros, string $titulo, string $premio, string $variant): string
    {
        $html = '<div class="bendecidos-grupo bendecidos-grupo--' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') . '">'
            . '<div class="bendecidos-grupo__head">'
            . '<strong>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</strong>'
            . '<span>' . htmlspecialchars($premio, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div><div class="bendecidos-grupo__nums">';

        foreach ($numeros as $raw) {
            $raw = trim((string) $raw);
            $played = $raw !== '' && ($raw[0] === 'x' || $raw[0] === 'X');
            $num = $played ? substr($raw, 1) : $raw;
            $empty = $num === '';
            $cls = 'bendecido-num';
            if ($empty) {
                $cls .= ' is-empty';
            }
            if ($played) {
                $cls .= ' tachado';
            }
            $html .= '<span class="' . $cls . '">' . ($empty ? '—' : htmlspecialchars($num, ENT_QUOTES, 'UTF-8')) . '</span>';
        }

        $html .= '</div><small class="bendecidos-grupo__nota">Pago inmediato</small></div>';

        return $html;
    }
}
