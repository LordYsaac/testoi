<?php

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';

    public function buscarPorUsername(string $username): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.username = :username
              LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    /** Lista plana de claves de permiso ("clientes.crear", ...) que tiene un rol */
    public function obtenerPermisos(int $rolId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.clave
               FROM permisos p
               JOIN roles_permisos rp ON rp.permiso_id = p.id
              WHERE rp.rol_id = :rol_id'
        );
        $stmt->execute(['rol_id' => $rolId]);
        return array_column($stmt->fetchAll(), 'clave');
    }

    public function conRol(): array
    {
        $sql = 'SELECT u.id, u.codigo, u.nombre, u.apellido, u.email, u.username, u.telefono,
                       u.foto, u.estado, u.ultimo_login, r.nombre AS rol_nombre
                  FROM usuarios u
                  JOIN roles r ON r.id = u.rol_id
                 ORDER BY u.nombre, u.apellido';
        return $this->db->query($sql)->fetchAll();
    }

    public function emailExiste(string $email, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM usuarios WHERE email = :email';
        $params = ['email' => $email];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function usernameExiste(string $username, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM usuarios WHERE username = :username';
        $params = ['username' => $username];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function siguienteCodigo(): string
    {
        $stmt = $this->db->query(
            "SELECT IFNULL(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1 AS siguiente
               FROM usuarios WHERE codigo REGEXP '^USR-[0-9]+$'"
        );
        $siguiente = (int) $stmt->fetch()['siguiente'];
        return 'USR-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /** Doctores/optometras activos, para selects de citas/expedientes/recetas */
    public function doctoresActivos(): array
    {
        $sql = "SELECT u.id, u.nombre, u.apellido
                  FROM usuarios u
                  JOIN roles r ON r.id = u.rol_id
                 WHERE r.nombre IN ('Doctor/Oftalmologo', 'Optometra') AND u.estado = 'activo'
                 ORDER BY u.nombre";
        return $this->db->query($sql)->fetchAll();
    }
}
