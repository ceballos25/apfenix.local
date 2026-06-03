<?php

/**
 * Hash de contraseña compatible con la API (post.controller.php).
 * La API usa: crypt($password, '$2a$07$azybxcags23425sdg23sdfhsd$')
 */
function apiHashPassword(string $plainPassword): string
{
    return crypt($plainPassword, '$2a$07$azybxcags23425sdg23sdfhsd$');
}

/**
 * Verifica contraseña contra hash almacenado por la API.
 */
function apiVerifyPassword(string $plainPassword, string $storedHash): bool
{
    return hash_equals($storedHash, apiHashPassword($plainPassword));
}
