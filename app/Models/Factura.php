<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Mismo patron de agregado transaccional que Compra, mirado desde el lado
 * de la venta: encabezado + detalle + movimiento de inventario (salida) +
 * pago(s) + movimiento de caja, todo en una sola transaccion. Ademas asigna
 * el NCF de forma segura (SELECT ... FOR UPDATE) para no repetir numeros
 * si dos ventas se registran al mismo tiempo.
 */
class Factura extends Model
{
    protected string $table = 'facturas';

    private const TIPOS_CON_EFECTO_COMPLETO = ['venta_producto', 'venta_medica', 'mixta'];

    /**
     * @throws \RuntimeException si el stock es insuficiente o el NCF esta agotado
     */
    public function crearCompleta(array $encabezado, array $lineas, array $pagos, int $usuarioId): int
    {
        return Database::transaction(function () use ($encabezado, $lineas, $pagos, $usuarioId) {
            $esVentaCompleta = in_array($encabezado['tipo'], self::TIPOS_CON_EFECTO_COMPLETO, true);

            $subtotal = 0.0;
            $descuentoTotal = 0.0;
            foreach ($lineas as $linea) {
                $subtotal += (float) $linea['cantidad'] * (float) $linea['precio_unitario'];
                $descuentoTotal += (float) $linea['descuento'];
            }
            $baseImponible = max(0, $subtotal - $descuentoTotal);
            $itbisPorcentaje = $esVentaCompleta ? (float) ($encabezado['itbis_porcentaje'] ?? 0) : 0;
            $itbis = round($baseImponible * ($itbisPorcentaje / 100), 2);
            $total = round($baseImponible + $itbis, 2);

            // Los pagos se procesan en el orden en que se ingresaron, limitando cada uno
            // a lo que realmente falta por cubrir. Lo que exceda en un pago EN EFECTIVO
            // se registra como "vuelto" (cambio) y NO se cuenta como ingreso de caja ni
            // como pago aplicado -- evita que el efectivo esperado al cerrar caja quede
            // inflado por el cambio que se le devolvio al cliente.
            $saldoPorCubrir = $total;
            $vuelto = 0.0;
            $pagosAplicados = [];

            foreach ($pagos as $pago) {
                $montoIngresado = (float) $pago['monto'];
                if ($montoIngresado <= 0) {
                    continue;
                }

                $aplicado = round(min($montoIngresado, max(0, $saldoPorCubrir)), 2);
                $excedente = round($montoIngresado - $aplicado, 2);

                if ($excedente > 0 && $pago['metodo_pago'] === 'efectivo') {
                    $vuelto += $excedente;
                }
                // Si el excedente viene de un metodo distinto a efectivo (tarjeta,
                // transferencia, cheque), simplemente no se aplica: no existe "vuelto"
                // fisico en esos medios: lo mas probable es que sea un error de captura.

                if ($aplicado > 0) {
                    $pagosAplicados[] = ['metodo_pago' => $pago['metodo_pago'], 'monto' => $aplicado, 'referencia' => $pago['referencia'] ?? null];
                }

                $saldoPorCubrir = round($saldoPorCubrir - $aplicado, 2);
            }

            $saldoPendiente = $esVentaCompleta ? max(0, $saldoPorCubrir) : 0;
            $vuelto = $esVentaCompleta ? round($vuelto, 2) : 0;
            $estado = !$esVentaCompleta ? 'pendiente' : ($saldoPendiente <= 0 ? 'pagada' : 'pendiente');

            $ncf = null;
            $tipoNcf = null;
            if ($esVentaCompleta && !empty($encabezado['tipo_ncf'])) {
                [$ncf, $tipoNcf] = $this->asignarSiguienteNcf($encabezado['tipo_ncf']);
            }

            $facturaId = $this->create([
                'tipo'            => $encabezado['tipo'],
                'ncf'             => $ncf,
                'tipo_ncf'        => $tipoNcf,
                'cliente_id'      => $encabezado['cliente_id'],
                'usuario_id'      => $usuarioId,
                'fecha'           => date('Y-m-d H:i:s'),
                'subtotal'        => $subtotal,
                'descuento'       => $descuentoTotal,
                'itbis'           => $itbis,
                'total'           => $total,
                'condicion_pago'  => $encabezado['condicion_pago'] ?? 'contado',
                'saldo_pendiente' => $saldoPendiente,
                'vuelto'          => $vuelto,
                'estado'          => $estado,
                'observaciones'   => $encabezado['observaciones'] ?? null,
            ]);

            $productoModel = new Producto();

            foreach ($lineas as $linea) {
                $productoId = !empty($linea['producto_id']) ? (int) $linea['producto_id'] : null;
                $cantidad = (float) $linea['cantidad'];
                $precioUnitario = (float) $linea['precio_unitario'];
                $descuentoLinea = (float) $linea['descuento'];
                $subtotalLinea = round(($cantidad * $precioUnitario) - $descuentoLinea, 2);

                $stmt = $this->db->prepare(
                    'INSERT INTO facturas_detalle (factura_id, producto_id, descripcion, cantidad, precio_unitario, descuento, subtotal)
                     VALUES (:factura_id, :producto_id, :descripcion, :cantidad, :precio_unitario, :descuento, :subtotal)'
                );
                $stmt->execute([
                    'factura_id' => $facturaId, 'producto_id' => $productoId, 'descripcion' => $linea['descripcion'],
                    'cantidad' => $cantidad, 'precio_unitario' => $precioUnitario, 'descuento' => $descuentoLinea, 'subtotal' => $subtotalLinea,
                ]);

                if ($esVentaCompleta && $productoId !== null) {
                    $stockActual = (int) ($productoModel->find($productoId)['stock_actual'] ?? 0);
                    if ($stockActual < $cantidad) {
                        throw new \RuntimeException("Stock insuficiente para \"{$linea['descripcion']}\" (disponible: {$stockActual}, solicitado: {$cantidad}).");
                    }
                    $productoModel->registrarMovimiento(
                        $productoId, 'salida', -1 * (int) $cantidad,
                        'Factura #' . $facturaId, $usuarioId, 'factura', $facturaId
                    );
                }
            }

            $cajaAbierta = (new CajaSesion())->sesionAbiertaDe($usuarioId);

            foreach ($pagosAplicados as $pago) {
                $stmt = $this->db->prepare(
                    'INSERT INTO factura_pagos (factura_id, metodo_pago, monto, referencia, fecha, usuario_id)
                     VALUES (:factura_id, :metodo, :monto, :referencia, NOW(), :usuario_id)'
                );
                $stmt->execute([
                    'factura_id' => $facturaId, 'metodo' => $pago['metodo_pago'], 'monto' => $pago['monto'],
                    'referencia' => $pago['referencia'] ?? null, 'usuario_id' => $usuarioId,
                ]);

                if ($cajaAbierta) {
                    (new CajaSesion())->registrarMovimiento(
                        (int) $cajaAbierta['id'], 'venta', 'Factura #' . $facturaId . ' (' . $pago['metodo_pago'] . ')',
                        (float) $pago['monto'], 'factura', $facturaId, $usuarioId
                    );
                }
            }

            return $facturaId;
        });
    }

    /** SELECT ... FOR UPDATE dentro de la misma transaccion para evitar NCF duplicados en ventas simultaneas */
    private function asignarSiguienteNcf(string $tipoNcf): array
    {
        $stmt = $this->db->prepare('SELECT * FROM configuracion_ncf WHERE tipo_ncf = :tipo AND estado = "activo" FOR UPDATE');
        $stmt->execute(['tipo' => $tipoNcf]);
        $config = $stmt->fetch();

        if (!$config) {
            throw new \RuntimeException("No hay una secuencia de NCF activa para el tipo {$tipoNcf}. Configúrela en configuracion_ncf.");
        }
        if ((int) $config['secuencia_actual'] > (int) $config['secuencia_final']) {
            throw new \RuntimeException("La secuencia de NCF {$tipoNcf} esta agotada. Solicite una nueva autorizacion a la DGII.");
        }

        $ncf = $config['tipo_ncf'] . str_pad((string) $config['secuencia_actual'], 8, '0', STR_PAD_LEFT);

        $this->db->prepare('UPDATE configuracion_ncf SET secuencia_actual = secuencia_actual + 1 WHERE tipo_ncf = :tipo')
                  ->execute(['tipo' => $tipoNcf]);

        return [$ncf, $tipoNcf];
    }

    public function obtenerCompleta(int $id): array|false
    {
        $sql = 'SELECT f.*, cl.nombres AS cliente_nombres, cl.apellidos AS cliente_apellidos, cl.cedula_pasaporte,
                       CONCAT(u.nombre, " ", u.apellido) AS vendedor
                  FROM facturas f
                  JOIN clientes cl ON cl.id = f.cliente_id
                  JOIN usuarios u ON u.id = f.usuario_id
                 WHERE f.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $factura = $stmt->fetch();
        if (!$factura) {
            return false;
        }

        $detalle = $this->db->prepare('SELECT * FROM facturas_detalle WHERE factura_id = :id');
        $detalle->execute(['id' => $id]);
        $factura['lineas'] = $detalle->fetchAll();

        $pagos = $this->db->prepare('SELECT * FROM factura_pagos WHERE factura_id = :id ORDER BY fecha');
        $pagos->execute(['id' => $id]);
        $factura['pagos'] = $pagos->fetchAll();

        return $factura;
    }

    public function listar(string $busqueda = '', string $estado = '', int $limite = 30): array
    {
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = '(cl.nombres LIKE :busqueda OR cl.apellidos LIKE :busqueda OR f.ncf LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($estado !== '') {
            $condiciones[] = 'f.estado = :estado';
            $params['estado'] = $estado;
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT f.id, f.fecha, f.tipo, f.ncf, f.total, f.saldo_pendiente, f.estado,
                       CONCAT(cl.nombres,' ',cl.apellidos) AS cliente
                  FROM facturas f JOIN clientes cl ON cl.id = f.cliente_id
                  {$where}
                 ORDER BY f.fecha DESC LIMIT " . (int) $limite;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Anula la factura y repone al inventario lo que se habia descontado */
    public function anular(int $id, string $motivo, int $usuarioId): void
    {
        Database::transaction(function () use ($id, $motivo, $usuarioId) {
            $factura = $this->obtenerCompleta($id);
            if (!$factura) {
                throw new \RuntimeException('Factura no encontrada.');
            }
            if ($factura['estado'] === 'anulada') {
                throw new \RuntimeException('Esta factura ya esta anulada.');
            }

            $productoModel = new Producto();
            foreach ($factura['lineas'] as $linea) {
                if ($linea['producto_id'] !== null) {
                    $productoModel->registrarMovimiento(
                        (int) $linea['producto_id'], 'ajuste', (int) $linea['cantidad'],
                        'Anulacion factura #' . $id . ': ' . $motivo, $usuarioId, 'factura', $id
                    );
                }
            }

            $this->update($id, ['estado' => 'anulada', 'saldo_pendiente' => 0]);
        });
    }

    public function pendientesDeCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, fecha, total, saldo_pendiente FROM facturas
              WHERE cliente_id = :cid AND saldo_pendiente > 0 AND estado <> 'anulada'
              ORDER BY fecha"
        );
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function registrarAbono(int $facturaId, float $monto, int $usuarioId): void
    {
        Database::transaction(function () use ($facturaId, $monto, $usuarioId) {
            $factura = $this->find($facturaId);
            if (!$factura) {
                throw new \RuntimeException('Factura no encontrada.');
            }
            $nuevoSaldo = max(0, (float) $factura['saldo_pendiente'] - $monto);
            $this->update($facturaId, [
                'saldo_pendiente' => $nuevoSaldo,
                'estado' => $nuevoSaldo <= 0 ? 'pagada' : 'pendiente',
            ]);

            $stmt = $this->db->prepare(
                'INSERT INTO abonos_clientes (cliente_id, factura_id, monto, metodo_pago, fecha, usuario_id)
                 VALUES (:cliente_id, :factura_id, :monto, :metodo, NOW(), :usuario_id)'
            );
            $stmt->execute([
                'cliente_id' => $factura['cliente_id'], 'factura_id' => $facturaId,
                'monto' => $monto, 'metodo' => 'efectivo', 'usuario_id' => $usuarioId,
            ]);
        });
    }
}
