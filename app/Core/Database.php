<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexion PDO unica (singleton) a MySQL/MariaDB.
 * Usa PDO + prepared statements en todo el sistema (nunca concatenar SQL).
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // usar prepared statements reales del driver
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
                ]);
            } catch (PDOException $e) {
                error_log('[DB] Error de conexion: ' . $e->getMessage());
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    throw $e;
                }
                http_response_code(500);
                die('No se pudo conectar a la base de datos. Verifique config/database.php o el archivo .env.');
            }
        }

        return self::$instance;
    }

    /**
     * Fija variables de sesion de MySQL (@app_usuario_id, @app_ip) que los
     * triggers de auditoria (ver database/schema_logic.sql) usan para saber
     * QUIEN realizo un cambio, sin depender de que cada trigger reciba el
     * usuario como parametro.
     */
    public static function setContextoAuditoria(?int $usuarioId, ?string $ip): void
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare('SET @app_usuario_id = :uid, @app_ip = :ip');
        $stmt->execute(['uid' => $usuarioId, 'ip' => $ip]);
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getInstance();
        $pdo->beginTransaction();
        try {
            $resultado = $callback($pdo);
            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
