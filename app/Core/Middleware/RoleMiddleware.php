<?php

namespace App\Core\Middleware;

use App\Core\Auth;

/**
 * Uso en routes/web.php:
 *   [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.crear']]
 */
class RoleMiddleware
{
    public function __construct(private string $permiso)
    {
    }

    public function handle(): void
    {
        if (!Auth::puede($this->permiso)) {
            http_response_code(403);
            require __DIR__ . '/../../Views/errors/403.php';
            exit;
        }
    }
}
