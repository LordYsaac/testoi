<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Autoloader de Composer para dependencias opcionales (Dompdf, PHPMailer...)
// si ya se ejecuto `composer install`. El nucleo de la app NO depende de esto.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../app/Core/autoload.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

Session::start();

// --- Cabeceras de seguridad basicas (complementan las de public/.htaccess) ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https: data:;");

// Contexto de auditoria para los triggers de base de datos (ver Database::setContextoAuditoria)
if (Auth::check()) {
    try {
        Database::setContextoAuditoria(Auth::id(), Request::ip());
    } catch (\Throwable $e) {
        error_log('[Bootstrap] No se pudo fijar contexto de auditoria: ' . $e->getMessage());
    }
}

try {
    $rutaSolicitada = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $basePath = trim(\App\Core\Url::base(), '/');
    if ($basePath !== '' && str_starts_with($rutaSolicitada, $basePath)) {
        $rutaSolicitada = trim(substr($rutaSolicitada, strlen($basePath)), '/');
    }

    $router = str_starts_with($rutaSolicitada, 'api/')
        ? require __DIR__ . '/../routes/api.php'
        : require __DIR__ . '/../routes/web.php';

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    error_log('[Fatal] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        require __DIR__ . '/../app/Views/errors/500.php';
    }
}
