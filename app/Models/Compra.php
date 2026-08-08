<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Igual que ExpedienteClinico, una Compra es un agregado: encabezado +
 * detalle + movimientos de inventario, todo en una sola transaccion. Si
 * algo falla a mitad de camino, no queda una compra a medias con el
 * inventario ya actualizado pero sin registrar en compras_detalle.
 */
class Compra extends Model
{
    protected string $table = 'compras';

    public function crearCompleta(array $encabezado, array $lineas, int $usuarioId, bool $actualizarCosto = true): int
    {
        return Database::transaction(function () use ($encabezado, $lineas, $usuarioId, $actualizarCosto) {
            $subtotal = 0.0;
            foreach ($lineas as $linea) {
                $subtotal += (float) $linea['cantidad'] * (float) $linea['costo_unitario'];
            }
            $itbisPorcentaje = (float) ($encabezado['itbis_porcentaje'] ?? 0);
            $itbis = round($subtotal * ($itbisPorcentaje / 100), 2);
            $total = round($subtotal + $itbis, 2);

            $compraId = $this->create([
                'proveedor_id'             => $encabezado['proveedor_id'],
                'orden_compra_id'          => $encabezado['orden_compra_id'] ?? null,
                'numero_factura_proveedor' => $encabezado['numero_factura_proveedor'] ?? null,
                'fecha'                    => $encabezado['fecha'],
                'subtotal'                 => $subtotal,
                'itbis'                    => $itbis,
                'total'                    => $total,
                'saldo_pendiente'          => $total,
                'estado_pago'              => 'pendiente',
                'created_by'               => $usuarioId,
            ]);

            $productoModel = new Producto();

            foreach ($lineas as $linea) {
                $productoId = (int) $linea['producto_id'];
                $cantidad = (int) $linea['cantidad'];
                $costoUnitario = (float) $linea['costo_unitario'];
                $subtotalLinea = round($cantidad * $costoUnitario, 2);

                $stmt = $this->db->prepare(
                    'INSERT INTO compras_detalle (compra_id, producto_id, cantidad, costo_unitario, subtotal)
                     VALUES (:compra_id, :producto_id, :cantidad, :costo_unitario, :subtotal)'
                );
                $stmt->execute([
                    'compra_id' => $compraId, 'producto_id' => $productoId, 'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario, 'subtotal' => $subtotalLinea,
                ]);

                // Movimiento de entrada -> el trigger actualiza productos.stock_actual
                $productoModel->registrarMovimiento(
                    $productoId, 'entrada', $cantidad,
                    'Compra #' . $compraId, $usuarioId, 'compra', $compraId, $costoUnitario
                );

                if ($actualizarCosto) {
                    $productoModel->update($productoId, ['costo' => $costoUnitario]);
                }
            }

            return $compraId;
        });
    }

    public function obtenerCompleta(int $id): array|false
    {
        $sql = 'SELECT c.*, pr.nombre AS proveedor_nombre, pr.rnc AS proveedor_rnc,
                       CONCAT(u.nombre, " ", u.apellido) AS creado_por
                  FROM compras c
                  JOIN proveedores pr ON pr.id = c.proveedor_id
                  JOIN usuarios u ON u.id = c.created_by
                 WHERE c.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch();
        if (!$compra) {
            return false;
        }

        $detalle = $this->db->prepare(
            'SELECT cd.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo
               FROM compras_detalle cd JOIN productos p ON p.id = cd.producto_id
              WHERE cd.compra_id = :id'
        );
        $detalle->execute(['id' => $id]);
        $compra['lineas'] = $detalle->fetchAll();

        $pagos = $this->db->prepare('SELECT * FROM pagos_proveedores WHERE compra_id = :id ORDER BY fecha DESC');
        $pagos->execute(['id' => $id]);
        $compra['pagos'] = $pagos->fetchAll();

        return $compra;
    }

    public function listar(int $limite = 30): array
    {
        $sql = "SELECT c.id, c.fecha, c.total, c.saldo_pendiente, c.estado_pago,
                       pr.nombre AS proveedor_nombre
                  FROM compras c JOIN proveedores pr ON pr.id = c.proveedor_id
                 ORDER BY c.fecha DESC LIMIT " . (int) $limite;
        return $this->db->query($sql)->fetchAll();
    }

    public function registrarPago(int $compraId, float $monto, string $metodoPago, ?string $referencia, int $usuarioId): void
    {
        Database::transaction(function () use ($compraId, $monto, $metodoPago, $referencia, $usuarioId) {
            $compra = $this->find($compraId);
            if (!$compra) {
                throw new \RuntimeException('Compra no encontrada.');
            }

            $stmt = $this->db->prepare(
                'INSERT INTO pagos_proveedores (compra_id, proveedor_id, monto, metodo_pago, referencia, fecha, usuario_id)
                 VALUES (:compra_id, :proveedor_id, :monto, :metodo_pago, :referencia, NOW(), :usuario_id)'
            );
            $stmt->execute([
                'compra_id' => $compraId, 'proveedor_id' => $compra['proveedor_id'], 'monto' => $monto,
                'metodo_pago' => $metodoPago, 'referencia' => $referencia, 'usuario_id' => $usuarioId,
            ]);

            $nuevoSaldo = max(0, (float) $compra['saldo_pendiente'] - $monto);
            $nuevoEstado = $nuevoSaldo <= 0 ? 'pagada' : ($nuevoSaldo < (float) $compra['total'] ? 'parcial' : 'pendiente');

            $this->update($compraId, ['saldo_pendiente' => $nuevoSaldo, 'estado_pago' => $nuevoEstado]);
        });
    }
}
