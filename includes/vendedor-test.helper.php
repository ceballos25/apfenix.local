<?php

/**
 * Detecta cuentas vendedor creadas solo para pruebas (seed / test).
 */
function esVendedorPrueba(string $email): bool
{
    $email = strtolower(trim($email));

    if ($email === '') {
        return true;
    }

    if (str_starts_with($email, 'seed.')) {
        return true;
    }

    if (str_starts_with($email, 'test.')) {
        return true;
    }

    return preg_match('/^(seed|test)\.vendedor\d+$/', $email) === 1;
}
