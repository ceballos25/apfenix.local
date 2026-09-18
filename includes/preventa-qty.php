<?php

/**
 * Cálculo de extras de preventa independiente del OPcache de otras clases.
 * 3→4, 5→7, 8→10, 10→13, 20→26, 25→33, 50→65
 */
function apfenix_cantidad_entregada(int $pagados): int
{
    if ($pagados < 3) {
        return $pagados;
    }

    $tz = new DateTimeZone('America/Bogota');
    $now = new DateTime('now', $tz);
    $start = new DateTime('2026-09-16 00:00:00', $tz);
    $end = new DateTime('2026-09-25 23:59:59', $tz);

    if ($now < $start || $now > $end) {
        return $pagados;
    }

    return $pagados + max(1, (int) round($pagados * 0.30));
}
