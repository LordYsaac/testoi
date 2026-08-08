<?php

namespace App\Core;

/**
 * Proteccion CSRF por token de sesion. Todo formulario POST debe incluir
 * el campo oculto generado por Csrf::field(), y toda ruta POST debe llamar
 * Csrf::verifyRequest() (el Router lo hace automaticamente, ver Router::dispatch).
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyRequest(): void
    {
        if (Request::isPost() && !self::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            require __DIR__ . '/../Views/errors/419.php';
            exit;
        }
    }
}
