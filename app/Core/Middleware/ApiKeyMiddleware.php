<?php

namespace App\Core\Middleware;

/**
 * Autenticacion para la API REST via cabecera "X-API-Key". Independiente
 * de la sesion de navegador (AuthMiddleware), pensado para integraciones
 * servidor-a-servidor (ver docs/API.md).
 */
class ApiKeyMiddleware
{
    public function handle(): void
    {
        $config = require __DIR__ . '/../../../config/api.php';
        $llaveEnviada = $_SERVER['HTTP_X_API_KEY'] ?? '';

        $valida = false;
        foreach ($config['keys'] as $llaveValida) {
            if ($llaveEnviada !== '' && hash_equals($llaveValida, $llaveEnviada)) {
                $valida = true;
                break;
            }
        }

        if (!$valida) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'API key invalida o ausente. Envie la cabecera X-API-Key.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
