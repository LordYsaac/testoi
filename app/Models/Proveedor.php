<?php

namespace App\Models;

use App\Core\Model;

class Proveedor extends Model
{
    protected string $table = 'proveedores';

    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = '(nombre LIKE :busqueda OR contacto_nombre LIKE :busqueda OR rnc LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($estado !== '') {
            $condiciones[] = 'estado = :estado';
            $params['estado'] = $estado;
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $stmt = $this->db->prepare("SELECT * FROM proveedores {$where} ORDER BY nombre");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Historial de compras y estado de cuenta de un proveedor */
    public function historialCompras(int $proveedorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, fecha, numero_factura_proveedor, total, saldo_pendiente, estado_pago
               FROM compras WHERE proveedor_id = :id ORDER BY fecha DESC'
        );
        $stmt->execute(['id' => $proveedorId]);
        return $stmt->fetchAll();
    }

    public function estadoCuenta(int $proveedorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total),0) AS total_comprado, COALESCE(SUM(saldo_pendiente),0) AS saldo_pendiente
               FROM compras WHERE proveedor_id = :id"
        );
        $stmt->execute(['id' => $proveedorId]);
        return $stmt->fetch();
    }
}
