<?php

namespace App\Models;

use App\Core\Model;

class Receta extends Model
{
    protected string $table = 'recetas';

    public function crear(array $data): int
    {
        $data['codigo_validacion'] = generar_codigo_validacion();
        return $this->create($data);
    }

    public function obtenerCompleta(int $id): array|false
    {
        $sql = 'SELECT r.*,
                       cl.nombres AS cliente_nombres, cl.apellidos AS cliente_apellidos,
                       cl.codigo_cliente, fn_edad(cl.fecha_nacimiento) AS cliente_edad,
                       cl.cedula_pasaporte,
                       CONCAT(u.nombre, " ", u.apellido) AS doctor_nombre,
                       u.cmd_colegiado AS doctor_colegiado, u.firma_digital AS doctor_firma
                  FROM recetas r
                  JOIN clientes cl ON cl.id = r.cliente_id
                  JOIN usuarios u ON u.id = r.doctor_id
                 WHERE r.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function porCodigoValidacion(string $codigo): array|false
    {
        $sql = 'SELECT r.id, r.fecha, r.estado, r.od_esfera, r.od_cilindro, r.od_eje, r.od_adicion, r.od_dp,
                       r.oi_esfera, r.oi_cilindro, r.oi_eje, r.oi_adicion, r.oi_dp,
                       r.tipo_lente, r.material, r.tratamiento_lente,
                       cl.nombres AS cliente_nombres, cl.apellidos AS cliente_apellidos,
                       CONCAT(u.nombre, " ", u.apellido) AS doctor_nombre, u.cmd_colegiado AS doctor_colegiado
                  FROM recetas r
                  JOIN clientes cl ON cl.id = r.cliente_id
                  JOIN usuarios u ON u.id = r.doctor_id
                 WHERE r.codigo_validacion = :codigo';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch();
    }

    public function porCliente(int $clienteId): array
    {
        $sql = 'SELECT r.id, r.fecha, r.estado, r.tipo_lente,
                       CONCAT(u.nombre, " ", u.apellido) AS doctor_nombre
                  FROM recetas r
                  JOIN usuarios u ON u.id = r.doctor_id
                 WHERE r.cliente_id = :cid
                 ORDER BY r.fecha DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function anular(int $id): bool
    {
        return $this->update($id, ['estado' => 'anulada']);
    }
}
