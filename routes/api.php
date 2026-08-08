<?php

declare(strict_types=1);

use App\Controllers\Api\ClientesApiController;
use App\Core\Middleware\ApiKeyMiddleware;
use App\Core\Router;

// Router independiente, SIN verificacion CSRF (la autenticacion es por
// cabecera X-API-Key, pensada para integraciones servidor-a-servidor).
$router = new Router(exigirCsrf: false);

$router->get('api/v1/clientes', [ClientesApiController::class, 'index'], [ApiKeyMiddleware::class]);
$router->get('api/v1/clientes/{id}', [ClientesApiController::class, 'show'], [ApiKeyMiddleware::class]);
$router->post('api/v1/clientes', [ClientesApiController::class, 'store'], [ApiKeyMiddleware::class]);

// Los proximos modulos (productos, facturas, citas...) se agregan aqui
// siguiendo el mismo patron: Controller -> Model -> ruta con ApiKeyMiddleware.

return $router;
