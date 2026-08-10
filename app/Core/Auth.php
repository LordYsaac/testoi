<?php

namespace App\Core;

use App\Models\Usuario;

/**
 * Autenticacion y autorizacion. Password hashing con password_hash/password_verify
 * (bcrypt), bloqueo temporal tras intentos fallidos repetidos, permisos
 * efectivos cacheados en sesion, y doble factor (TOTP) opcional por usuario.
 */
class Auth
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 15;

    public static function intentar(string $username, string $password): array
    {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorUsername($username);

        if (!$usuario) {
            self::registrarAuditoria(null, 'login_fallido', "Usuario no encontrado: {$username}");
            return ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'];
        }

        if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            $minutos = (int) ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
            return ['ok' => false, 'mensaje' => "Cuenta bloqueada temporalmente por intentos fallidos. Intente de nuevo en {$minutos} min."];
        }

        if ($usuario['estado'] !== 'activo') {
            return ['ok' => false, 'mensaje' => 'Esta cuenta esta inactiva. Contacte al administrador.'];
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;
            $bloqueadoHasta = $intentos >= self::MAX_INTENTOS
                ? date('Y-m-d H:i:s', strtotime('+' . self::BLOQUEO_MINUTOS . ' minutes'))
                : null;

            $usuarioModel->update((int) $usuario['id'], [
                'intentos_fallidos' => $intentos,
                'bloqueado_hasta'   => $bloqueadoHasta,
            ]);
            self::registrarAuditoria((int) $usuario['id'], 'login_fallido', 'Contraseña incorrecta');

            return ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'];
        }

        // Contraseña correcta. Si tiene 2FA activo, NO completar la sesion todavia:
        // solo se marca "pendiente de codigo" hasta que Auth::completarDosFactor() lo confirme.
        if ((bool) $usuario['two_factor_activo']) {
            session_regenerate_id(true);
            $_SESSION['2fa_pendiente_id'] = (int) $usuario['id'];
            return ['ok' => true, 'requiere_2fa' => true];
        }

        // Rehash si el costo por defecto de PHP cambio desde que se creo el hash
        $nuevoHash = password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT) ? password_hash($password, PASSWORD_DEFAULT) : null;

        self::completarSesion($usuario, $usuarioModel, $nuevoHash);
        return ['ok' => true, 'requiere_2fa' => false, 'debe_cambiar_password' => (bool) $usuario['debe_cambiar_password']];
    }

    public static function hayDosFactorPendiente(): bool
    {
        return !empty($_SESSION['2fa_pendiente_id']);
    }

    /** Verifica el codigo TOTP del login en dos pasos y, si es correcto, completa la sesion */
    public static function completarDosFactor(string $codigo): array
    {
        if (empty($_SESSION['2fa_pendiente_id'])) {
            return ['ok' => false, 'mensaje' => 'No hay un inicio de sesion pendiente de verificar.'];
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->find((int) $_SESSION['2fa_pendiente_id']);

        if (!$usuario || !$usuario['two_factor_activo']) {
            unset($_SESSION['2fa_pendiente_id']);
            return ['ok' => false, 'mensaje' => 'Sesion invalida. Inicie sesion de nuevo.'];
        }

        if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            return ['ok' => false, 'mensaje' => 'Cuenta bloqueada temporalmente. Intente mas tarde.'];
        }

        if (!Totp::verificar($usuario['two_factor_secret'], $codigo)) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;
            $bloqueadoHasta = $intentos >= self::MAX_INTENTOS
                ? date('Y-m-d H:i:s', strtotime('+' . self::BLOQUEO_MINUTOS . ' minutes'))
                : null;
            $usuarioModel->update((int) $usuario['id'], ['intentos_fallidos' => $intentos, 'bloqueado_hasta' => $bloqueadoHasta]);
            self::registrarAuditoria((int) $usuario['id'], 'login_fallido', 'Codigo 2FA incorrecto');
            return ['ok' => false, 'mensaje' => 'Codigo incorrecto.'];
        }

        unset($_SESSION['2fa_pendiente_id']);
        self::completarSesion($usuario, $usuarioModel);
        return ['ok' => true, 'debe_cambiar_password' => (bool) $usuario['debe_cambiar_password']];
    }

    private static function completarSesion(array $usuario, Usuario $usuarioModel, ?string $nuevoHash = null): void
    {
        $datosActualizar = [
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_login'      => date('Y-m-d H:i:s'),
            'ultimo_login_ip'   => Request::ip(),
        ];
        if ($nuevoHash) {
            $datosActualizar['password_hash'] = $nuevoHash;
        }
        $usuarioModel->update((int) $usuario['id'], $datosActualizar);

        session_regenerate_id(true);
        $_SESSION['usuario_id']     = (int) $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
        $_SESSION['usuario_foto']   = $usuario['foto'];
        $_SESSION['rol_id']         = (int) $usuario['rol_id'];
        $_SESSION['rol_nombre']     = $usuario['rol_nombre'] ?? '';
        $_SESSION['permisos']       = $usuarioModel->obtenerPermisos((int) $usuario['rol_id']);

        Database::setContextoAuditoria((int) $usuario['id'], Request::ip());
        self::registrarAuditoria((int) $usuario['id'], 'login', 'Inicio de sesion exitoso');
    }

    public static function logout(): void
    {
        if (self::check()) {
            self::registrarAuditoria(self::id(), 'logout', 'Cierre de sesion');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    public static function nombre(): string
    {
        return $_SESSION['usuario_nombre'] ?? '';
    }

    public static function rolNombre(): string
    {
        return $_SESSION['rol_nombre'] ?? '';
    }

    /** Verifica un permiso puntual, ej: Auth::puede('clientes.crear') */
    public static function puede(string $permiso): bool
    {
        return in_array($permiso, $_SESSION['permisos'] ?? [], true);
    }

    private static function registrarAuditoria(?int $usuarioId, string $accion, string $detalle): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO auditoria (usuario_id, accion, modulo, datos_nuevos, ip_address, user_agent, created_at)
                 VALUES (:uid, :accion, :modulo, :detalle, :ip, :ua, NOW())'
            );
            $stmt->execute([
                'uid'     => $usuarioId,
                'accion'  => $accion,
                'modulo'  => 'auth',
                'detalle' => json_encode(['detalle' => $detalle], JSON_UNESCAPED_UNICODE),
                'ip'      => Request::ip(),
                'ua'      => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            error_log('[Auditoria] No se pudo registrar: ' . $e->getMessage());
        }
    }
}
