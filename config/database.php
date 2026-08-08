<?php
/**
 * Credenciales de base de datos. En produccion, definir estos valores en
 * el archivo .env (copiar desde .env.example) y NUNCA subir .env al
 * repositorio. Aqui solo se leen las variables de entorno resultantes.
 */

declare(strict_types=1);

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'optica_erp',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];
