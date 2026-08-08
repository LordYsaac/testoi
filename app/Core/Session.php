<?php

namespace App\Core;

/**
 * Arranque de sesion con configuracion de seguridad:
 * cookies HttpOnly + SameSite, regeneracion periodica de ID (mitiga
 * fijacion de sesion) y deteccion basica de expiracion por inactividad.
 */
class Session
{
    private const TIEMPO_REGENERAR = 1800; // 30 min
    private const TIEMPO_INACTIVIDAD = 3600; // 1 hora

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('OPTICA_SESSION');
        session_start();

        // Expira por inactividad
        if (!empty($_SESSION['_ultima_actividad']) && (time() - $_SESSION['_ultima_actividad']) > self::TIEMPO_INACTIVIDAD) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['_ultima_actividad'] = time();

        // Regenera el ID de sesion periodicamente
        if (empty($_SESSION['_creado'])) {
            $_SESSION['_creado'] = time();
        } elseif ((time() - $_SESSION['_creado']) > self::TIEMPO_REGENERAR) {
            session_regenerate_id(true);
            $_SESSION['_creado'] = time();
        }
    }

    /** Recupera y limpia los mensajes flash acumulados (usar una sola vez en el layout) */
    public static function consumirFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
