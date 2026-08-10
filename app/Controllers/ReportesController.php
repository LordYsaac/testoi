<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Request;

class ReportesController extends Controller
{
    public function index(): void
    {
        $this->view('reportes/index', []);
    }

    private function rangoFechas(): array
    {
        $desde = Request::input('desde') ?: date('Y-m-01');
        $hasta = Request::input('hasta') ?: date('Y-m-d');
        return [$desde, $hasta];
    }

    public function ventas(): void
    {
        [$desde, $hasta] = $this->rangoFechas();
        $db = Database::getInstance();
        $stmt = $db->prepare('CALL sp_reporte_ventas_periodo(:desde, :hasta)');
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $filas = $stmt->fetchAll();
        $stmt->closeCursor();

        $totales = [
            'facturas' => array_sum(array_column($filas, 'facturas')),
            'subtotal' => array_sum(array_column($filas, 'subtotal')),
            'descuento' => array_sum(array_column($filas, 'descuento')),
            'itbis' => array_sum(array_column($filas, 'itbis')),
            'total' => array_sum(array_column($filas, 'total')),
        ];

        if (Request::input('formato') === 'csv') {
            Csv::descargar('ventas_' . $desde . '_a_' . $hasta . '.csv', $filas, ['dia', 'facturas', 'subtotal', 'descuento', 'itbis', 'total']);
        }

        $this->view('reportes/ventas', compact('filas', 'totales', 'desde', 'hasta'));
    }

    public function productosMasVendidos(): void
    {
        [$desde, $hasta] = $this->rangoFechas();
        $sql = "SELECT p.codigo, p.nombre, cp.nombre AS categoria,
                       SUM(fd.cantidad) AS unidades_vendidas,
                       SUM(fd.subtotal) AS monto_vendido
                  FROM facturas_detalle fd
                  JOIN facturas f ON f.id = fd.factura_id
                  JOIN productos p ON p.id = fd.producto_id
                  JOIN categorias_productos cp ON cp.id = p.categoria_id
                 WHERE DATE(f.fecha) BETWEEN :desde AND :hasta AND f.estado <> 'anulada'
                 GROUP BY p.id, p.codigo, p.nombre, cp.nombre
                 ORDER BY unidades_vendidas DESC
                 LIMIT 50";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $filas = $stmt->fetchAll();

        if (Request::input('formato') === 'csv') {
            Csv::descargar('productos_mas_vendidos_' . $desde . '_a_' . $hasta . '.csv', $filas);
        }

        $this->view('reportes/productos_mas_vendidos', compact('filas', 'desde', 'hasta'));
    }

    public function clientesFrecuentes(): void
    {
        $filas = Database::getInstance()->query('SELECT * FROM vista_clientes_frecuentes LIMIT 100')->fetchAll();

        if (Request::input('formato') === 'csv') {
            Csv::descargar('clientes_frecuentes.csv', $filas);
        }

        $this->view('reportes/clientes_frecuentes', compact('filas'));
    }

    public function inventario(): void
    {
        $sql = "SELECT p.codigo, p.nombre, cp.nombre AS categoria, p.stock_actual, p.stock_minimo,
                       p.costo, p.precio, (p.stock_actual * p.costo) AS valor_costo, (p.stock_actual * p.precio) AS valor_venta
                  FROM productos p JOIN categorias_productos cp ON cp.id = p.categoria_id
                 WHERE p.estado = 'activo'
                 ORDER BY cp.nombre, p.nombre";
        $filas = Database::getInstance()->query($sql)->fetchAll();
        $valorTotalCosto = array_sum(array_column($filas, 'valor_costo'));
        $valorTotalVenta = array_sum(array_column($filas, 'valor_venta'));

        if (Request::input('formato') === 'csv') {
            Csv::descargar('inventario_valorizado.csv', $filas);
        }

        $this->view('reportes/inventario', compact('filas', 'valorTotalCosto', 'valorTotalVenta'));
    }

    public function pacientesAtendidos(): void
    {
        [$desde, $hasta] = $this->rangoFechas();
        $sql = "SELECT e.fecha, CONCAT(cl.nombres,' ',cl.apellidos) AS paciente,
                       CONCAT(u.nombre,' ',u.apellido) AS doctor, e.motivo_consulta
                  FROM expedientes_clinicos e
                  JOIN clientes cl ON cl.id = e.cliente_id
                  LEFT JOIN usuarios u ON u.id = e.doctor_id
                 WHERE DATE(e.fecha) BETWEEN :desde AND :hasta
                 ORDER BY e.fecha DESC";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $filas = $stmt->fetchAll();

        if (Request::input('formato') === 'csv') {
            Csv::descargar('pacientes_atendidos_' . $desde . '_a_' . $hasta . '.csv', $filas);
        }

        $this->view('reportes/pacientes_atendidos', compact('filas', 'desde', 'hasta'));
    }

    public function recetasEmitidas(): void
    {
        [$desde, $hasta] = $this->rangoFechas();
        $sql = "SELECT r.fecha, CONCAT(cl.nombres,' ',cl.apellidos) AS paciente,
                       CONCAT(u.nombre,' ',u.apellido) AS doctor, r.tipo_lente, r.estado
                  FROM recetas r
                  JOIN clientes cl ON cl.id = r.cliente_id
                  JOIN usuarios u ON u.id = r.doctor_id
                 WHERE DATE(r.fecha) BETWEEN :desde AND :hasta
                 ORDER BY r.fecha DESC";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $filas = $stmt->fetchAll();

        if (Request::input('formato') === 'csv') {
            Csv::descargar('recetas_emitidas_' . $desde . '_a_' . $hasta . '.csv', $filas);
        }

        $this->view('reportes/recetas_emitidas', compact('filas', 'desde', 'hasta'));
    }

    public function cuentas(): void
    {
        $db = Database::getInstance();
        $porCobrar = $db->query('SELECT * FROM vista_clientes_morosos ORDER BY dias_vencido DESC')->fetchAll();
        $porPagar = $db->query('SELECT * FROM vista_cuentas_por_pagar ORDER BY saldo_total DESC')->fetchAll();

        if (Request::input('formato') === 'csv' && Request::input('tipo') === 'por_pagar') {
            Csv::descargar('cuentas_por_pagar.csv', $porPagar);
        }
        if (Request::input('formato') === 'csv') {
            Csv::descargar('cuentas_por_cobrar.csv', $porCobrar);
        }

        $this->view('reportes/cuentas', compact('porCobrar', 'porPagar'));
    }
}
