<?php

namespace App\Models;

use App\Core\Model;

class Rol extends Model
{
    protected string $table = 'roles';

    public function todosConConteoUsuarios(): array
    {
        $sql = "SELECT r.*, COUNT(u.id) AS total_usuarios
                  FROM roles r
                  LEFT JOIN usuarios u ON u.rol_id = r.id
                 GROUP BY r.id
                 ORDER BY r.id";
        return $this->db->query($sql)->fetchAll();
    }

    /** Todos los permisos agrupados por modulo, con bandera de si el rol dado los tiene */
    public function matrizPermisos(int $rolId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.modulo, p.accion, p.descripcion,
                    EXISTS(
                        SELECT 1 FROM roles_permisos rp
                         WHERE rp.rol_id = :rol_id AND rp.permiso_id = p.id
                    ) AS asignado
               FROM permisos p
              ORDER BY p.modulo, p.accion'
        );
        $stmt->execute(['rol_id' => $rolId]);
        $permisos = $stmt->fetchAll();

        $agrupado = [];
        foreach ($permisos as $permiso) {
            $agrupado[$permiso['modulo']][] = $permiso;
        }
        return $agrupado;
    }

    public function sincronizarPermisos(int $rolId, array $permisoIds): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM roles_permisos WHERE rol_id = :rol_id');
            $del->execute(['rol_id' => $rolId]);

            if (!empty($permisoIds)) {
                $ins = $this->db->prepare('INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (:rol_id, :permiso_id)');
                foreach ($permisoIds as $permisoId) {
                    $ins->execute(['rol_id' => $rolId, 'permiso_id' => (int) $permisoId]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
