<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Compra;
use App\Models\Configuracion;
use App\Models\Producto;
use App\Models\Proveedor;

class ComprasController extends Controller
{
    public function index(): void
    {
        $compras = (new Compra())->listar();
        $this->view('compras/index', compact('compras'));
    }

    public function crear(): void
    {
        $proveedores = (new Proveedor())->listar('', 'activo');
        $productos = (new Producto())->listar('', 0, 'activo', 500, 1);
        $empresa = Configuracion::empresa();
        $this->view('compras/form', compact('proveedores', 'productos', 'empresa'));
    }

    public function guardar(): void
    {
        $proveedorId = (int) Request::input('proveedor_id');
        $fecha = Request::input('fecha') ?: date('Y-m-d');
        $numeroFactura = Request::input('numero_factura_proveedor');
        $aplicaItbis = Request::input('aplica_itbis') === '1';

        $productoIds = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $costos = $_POST['costo_unitario'] ?? [];

        if ($proveedorId <= 0 || empty($productoIds)) {
            $this->flashError('Debe seleccionar un proveedor y al menos un producto.');
            $this->redirect('compras/crear');
            return;
        }

        $lineas = [];
        foreach ($productoIds as $i => $productoId) {
            $cantidad = (int) ($cantidades[$i] ?? 0);
            $costo = (float) ($costos[$i] ?? 0);
            if ((int) $productoId > 0 && $cantidad > 0) {
                $lineas[] = ['producto_id' => (int) $productoId, 'cantidad' => $cantidad, 'costo_unitario' => $costo];
            }
        }

        if (empty($lineas)) {
            $this->flashError('Agregue al menos una linea valida (producto y cantidad mayor a cero).');
            $this->redirect('compras/crear');
            return;
        }

        $empresa = Configuracion::empresa();
        $encabezado = [
            'proveedor_id'             => $proveedorId,
            'fecha'                    => $fecha,
            'numero_factura_proveedor' => $numeroFactura ?: null,
            'itbis_porcentaje'         => $aplicaItbis ? (float) ($empresa['itbis_porcentaje'] ?? 0) : 0,
        ];

        $id = (new Compra())->crearCompleta($encabezado, $lineas, Auth::id());

        $this->flashExito('Compra registrada. El inventario se actualizo automaticamente.');
        $this->redirect('compras/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $compra = (new Compra())->obtenerCompleta($id);
        if (!$compra) {
            $this->flashError('Compra no encontrada.');
            $this->redirect('compras');
            return;
        }
        $this->view('compras/ver', compact('compra'));
    }

    public function registrarPago(int $id): void
    {
        $monto = (float) Request::input('monto');
        $metodo = Request::input('metodo_pago');
        $referencia = Request::input('referencia');

        if ($monto <= 0) {
            $this->flashError('El monto debe ser mayor a cero.');
            $this->redirect('compras/ver/' . $id);
            return;
        }

        (new Compra())->registrarPago($id, $monto, $metodo, $referencia, Auth::id());
        $this->flashExito('Pago registrado correctamente.');
        $this->redirect('compras/ver/' . $id);
    }
}
