<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Factura;
use App\Models\Producto;

class FacturasController extends Controller
{
    public function index(): void
    {
        $busqueda = Request::input('q', '');
        $estado = Request::input('estado', '');
        $facturas = (new Factura())->listar($busqueda, $estado);
        $this->view('facturas/index', compact('facturas', 'busqueda', 'estado'));
    }

    public function crear(): void
    {
        $clienteId = (int) Request::input('cliente_id', 0);
        $cliente = $clienteId ? (new Cliente())->find($clienteId) : null;
        $productos = (new Producto())->listar('', 0, 'activo', 500, 1);
        $empresa = Configuracion::empresa();
        $cajaAbierta = (new CajaSesion())->sesionAbiertaDe(Auth::id());
        $this->view('facturas/form', compact('cliente', 'productos', 'empresa', 'cajaAbierta'));
    }

    public function guardar(): void
    {
        $clienteId = (int) Request::input('cliente_id');
        $tipo = Request::input('tipo', 'venta_producto');
        $tipoNcf = Request::input('tipo_ncf') ?: null;
        $condicionPago = Request::input('condicion_pago', 'contado');
        $aplicaItbis = Request::input('aplica_itbis') === '1';

        if ($clienteId <= 0) {
            $this->flashError('Debe seleccionar un cliente.');
            $this->redirect('facturas/crear');
            return;
        }

        $descripciones = $_POST['descripcion'] ?? [];
        $productoIds = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];
        $descuentos = $_POST['descuento_linea'] ?? [];

        $lineas = [];
        foreach ($descripciones as $i => $descripcion) {
            $cantidad = (float) ($cantidades[$i] ?? 0);
            if ($descripcion === '' || $cantidad <= 0) {
                continue;
            }
            $lineas[] = [
                'producto_id'     => !empty($productoIds[$i]) ? (int) $productoIds[$i] : null,
                'descripcion'     => $descripcion,
                'cantidad'        => $cantidad,
                'precio_unitario' => (float) ($precios[$i] ?? 0),
                'descuento'       => (float) ($descuentos[$i] ?? 0),
            ];
        }

        if (empty($lineas)) {
            $this->flashError('Agregue al menos una linea a la factura.');
            $this->redirect('facturas/crear');
            return;
        }

        $metodos = $_POST['metodo_pago'] ?? [];
        $montos = $_POST['monto_pago'] ?? [];
        $pagos = [];
        foreach ($metodos as $i => $metodo) {
            $monto = (float) ($montos[$i] ?? 0);
            if ($monto > 0) {
                $pagos[] = ['metodo_pago' => $metodo, 'monto' => $monto];
            }
        }

        $empresa = Configuracion::empresa();
        $encabezado = [
            'cliente_id'       => $clienteId,
            'tipo'             => $tipo,
            'tipo_ncf'         => $tipoNcf,
            'condicion_pago'   => $condicionPago,
            'observaciones'    => Request::input('observaciones'),
            'itbis_porcentaje' => $aplicaItbis ? (float) ($empresa['itbis_porcentaje'] ?? 0) : 0,
        ];

        try {
            $id = (new Factura())->crearCompleta($encabezado, $lineas, $pagos, Auth::id());
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('facturas/crear');
            return;
        }

        $this->flashExito('Factura registrada correctamente.');
        $this->redirect('facturas/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $factura = (new Factura())->obtenerCompleta($id);
        if (!$factura) {
            $this->flashError('Factura no encontrada.');
            $this->redirect('facturas');
            return;
        }
        $this->view('facturas/ver', compact('factura'));
    }

    public function imprimir(int $id): void
    {
        $factura = (new Factura())->obtenerCompleta($id);
        if (!$factura) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        $empresa = Configuracion::empresa();
        $this->viewRaw('facturas/imprimir', compact('factura', 'empresa'));
    }

    public function anular(int $id): void
    {
        $motivo = Request::input('motivo', 'Sin motivo especificado');
        try {
            (new Factura())->anular($id, $motivo, Auth::id());
            $this->flashExito('Factura anulada. El inventario fue repuesto.');
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('facturas/ver/' . $id);
    }
}
