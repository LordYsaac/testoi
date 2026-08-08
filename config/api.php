<?php
/**
 * Llaves de API validas para consumir routes/api.php (ver docs/API.md).
 * En produccion, definir API_KEYS en .env como lista separada por comas
 * y generar llaves largas y aleatorias (ej. bin2hex(random_bytes(32))).
 */

declare(strict_types=1);

$llaves = array_filter(array_map('trim', explode(',', (string) (getenv('API_KEYS') ?: ''))));

return [
    'keys' => $llaves ?: ['CAMBIA-ESTA-LLAVE-DE-DESARROLLO'],
];
