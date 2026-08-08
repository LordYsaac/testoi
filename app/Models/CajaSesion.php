<?php

namespace App\Models;

use App\Core\Model;

class CajaSesion extends Model
{
    protected string $table = 'caja_sesiones';

    public function sesionAbiertaDe(int $usuarioId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM caja_sesiones WHERE usuario_id = :uid AND estado = 'abierta' LIMIT 1");
        $stmt->execute(['uid' => $usuarioId]);
        return $stmt->fetch();
    }

    public function abrir(int $usuarioId, float $montoApertura, ?string $observaciones = null): int
    {
        if ($this->sesionAbiertaDe($usuarioId)) {
            throw new \RuntimeException('Ya tiene una caja abierta. Ciérrela antes de abrir una nueva.');
        }
        return $this->create([
            'usuario_id' => $usuarioId, 'monto_apertura' => $montoApertura,
            'fecha_apertura' => date('Y-m-d H:i:s'), 'estado' => 'abierta', 'observaciones' => $observaciones,
        ]);
    }

    /** Usa el procedimiento almacenado sp_cerrar_caja (calcula esperado y diferencia en el servidor de BD) */
    public function cerrar(int $cajaSesionId, float $montoDeclarado): float
    {
        $stmt = $this->db->prepare('CALL sp_cerrar_caja(:id, :monto, @diferencia)');
        $stmt->execute(['id' => $cajaSesionId, 'monto' => $montoDeclarado]);
        $stmt->closeCursor();
        $resultado = $this->db->query('SELECT @diferencia AS diferencia')->fetch();
        return (float) $resultado['diferencia'];
    }

    public function resumen(int $cajaSesionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo IN ('ingreso','venta') THEN monto ELSE 0 END),0) AS ingresos,
                    COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END),0) AS egresos,
                    COUNT(CASE WHEN tipo = 'venta' THEN 1 END) AS cantidad_ventas
               FROM caja_movimientos WHERE caja_sesion_id = :id"
        );
        $stmt->execute(['id' => $cajaSesionId]);
        return $stmt->fetch();
    }

    public function movimientos(int $cajaSesionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cm.*, CONCAT(u.nombre,' ',u.apellido) AS usuario
               FROM caja_movimientos cm JOIN usuarios u ON u.id = cm.usuario_id
              WHERE caja_sesion_id = :id ORDER BY fecha DESC"
        );
        $stmt->execute(['id' => $cajaSesionId]);
        return $stmt->fetchAll();
    }

    public function registrarMovimiento(int $cajaSesionId, string $tipo, string $concepto, float $monto, ?string $referenciaTipo, ?int $referenciaId, int $usuarioId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO caja_movimientos (caja_sesion_id, tipo, concepto, monto, referencia_tipo, referencia_id, fecha, usuario_id)
             VALUES (:id, :tipo, :concepto, :monto, :ref_tipo, :ref_id, NOW(), :usuario_id)'
        );
        $stmt->execute([
            'id' => $cajaSesionId, 'tipo' => $tipo, 'concepto' => $concepto, 'monto' => $monto,
            'ref_tipo' => $referenciaTipo, 'ref_id' => $referenciaId, 'usuario_id' => $usuarioId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function historial(int $limite = 20): array
    {
        $sql = "SELECT cs.*, CONCAT(u.nombre,' ',u.apellido) AS usuario
                  FROM caja_sesiones cs JOIN usuarios u ON u.id = cs.usuario_id
                 ORDER BY cs.fecha_apertura DESC LIMIT " . (int) $limite;
        return $this->db->query($sql)->fetchAll();
    }
}
