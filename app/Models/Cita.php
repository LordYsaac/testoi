<?php

namespace App\Models;

use App\Core\Model;

class Cita extends Model
{
    protected string $table = 'citas';

    /** Conteo de citas por dia en un mes, para pintar el calendario */
    public function conteoPorMes(int $anio, int $mes): array
    {
        $stmt = $this->db->prepare(
            "SELECT fecha, COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'cancelada' THEN 0 ELSE 1 END) AS activas
               FROM citas
              WHERE YEAR(fecha) = :anio AND MONTH(fecha) = :mes
              GROUP BY fecha"
        );
        $stmt->execute(['anio' => $anio, 'mes' => $mes]);
        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['fecha']] = $fila;
        }
        return $resultado;
    }

    public function porFecha(string $fecha): array
    {
        $sql = "SELECT c.*, CONCAT(cl.nombres,' ',cl.apellidos) AS paciente, cl.telefono, cl.id AS cliente_id,
                       CONCAT(u.nombre,' ',u.apellido) AS doctor_nombre
                  FROM citas c
                  JOIN clientes cl ON cl.id = c.cliente_id
                  LEFT JOIN usuarios u ON u.id = c.doctor_id
                 WHERE c.fecha = :fecha
                 ORDER BY c.hora";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    public function conDetalle(int $id): array|false
    {
        $sql = "SELECT c.*, CONCAT(cl.nombres,' ',cl.apellidos) AS paciente, cl.telefono, cl.id AS cliente_id,
                       CONCAT(u.nombre,' ',u.apellido) AS doctor_nombre
                  FROM citas c
                  JOIN clientes cl ON cl.id = c.cliente_id
                  LEFT JOIN usuarios u ON u.id = c.doctor_id
                 WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Citas futuras de un cliente, para mostrarlas en su ficha */
    public function proximasDeCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, CONCAT(u.nombre,' ',u.apellido) AS doctor_nombre
               FROM citas c LEFT JOIN usuarios u ON u.id = c.doctor_id
              WHERE c.cliente_id = :cid AND c.fecha >= CURDATE() AND c.estado <> 'cancelada'
              ORDER BY c.fecha, c.hora"
        );
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    /** Evita agendar dos citas del mismo doctor exactamente a la misma hora (aviso, no bloqueo estricto) */
    public function hayConflicto(?int $doctorId, string $fecha, string $hora, ?int $excluirId = null): bool
    {
        if (!$doctorId) {
            return false;
        }
        $sql = "SELECT COUNT(*) AS total FROM citas
                 WHERE doctor_id = :doctor_id AND fecha = :fecha AND hora = :hora AND estado <> 'cancelada'";
        $params = ['doctor_id' => $doctorId, 'fecha' => $fecha, 'hora' => $hora];
        if ($excluirId) {
            $sql .= ' AND id <> :excluir_id';
            $params['excluir_id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->update($id, ['estado' => $estado]);
    }
}
