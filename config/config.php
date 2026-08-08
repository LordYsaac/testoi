<?php
/**
 * Bootstrap de configuracion. Se incluye una unica vez desde public/index.php
 * y desde cualquier script de linea de comandos (cron/, scripts de migracion).
 */

declare(strict_types=1);

// --- Cargar .env (parser propio, sin dependencias) -------------------------
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor, " \t\n\r\0\x0B\"'");
        if (getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

// --- Constantes de la aplicacion --------------------------------------------
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_NAME', getenv('APP_NAME') ?: 'Sistema de Gestion de Optica');
define('APP_URL', rtrim((string) (getenv('APP_URL') ?: ''), '/'));
define('APP_ROOT', dirname(__DIR__));

// --- Errores y logs ----------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
$logDir = APP_ROOT . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// --- Zona horaria --------------------------------------------------------------
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Santo_Domingo');

// --- Limites de subida de archivos (tambien configurar en php.ini/LiteSpeed) --
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
