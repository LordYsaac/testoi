<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Producto;

class ProductosController extends Controller
{
    public function index(): void
    {
        $busqueda = Request::input('q', '');
        $categoriaId = (int) Request::input('categoria_id', 0);
        $estado = Request::input('estado', 'activo');
        $pagina = max(1, (int) Request::input('pagina', 1));
        $porPagina = 20;

        $productoModel = new Producto();
        $productos = $productoModel->listar($busqueda, $categoriaId, $estado, $porPagina, $pagina);
        $total = $productoModel->totalListar($busqueda, $categoriaId, $estado);
        $totalPaginas = (int) ceil($total / $porPagina);
        $categorias = $productoModel->categorias();

        $this->view('productos/index', compact('productos', 'busqueda', 'categoriaId', 'estado', 'pagina', 'totalPaginas', 'total', 'categorias'));
    }

    public function crear(): void
    {
        $productoModel = new Producto();
        $categorias = $productoModel->categorias();
        $proveedores = $productoModel->proveedoresActivos();
        $siguienteCodigo = $productoModel->siguienteCodigo();
        $this->view('productos/form', ['producto' => null, 'categorias' => $categorias, 'proveedores' => $proveedores, 'siguienteCodigo' => $siguienteCodigo, 'errores' => []]);
    }

    public function guardar(): void
    {
        $data = Request::only(['codigo', 'codigo_barras', 'categoria_id', 'nombre', 'marca', 'modelo', 'color', 'material', 'proveedor_id', 'costo', 'precio', 'stock_minimo', 'ubicacion', 'lote', 'fecha_vencimiento']);

        $v = new Validator($data);
        $v->required('nombre')->required('categoria_id')->numeric('costo')->numeric('precio');

        $productoModel = new Producto();
        if ($v->fails() || $productoModel->codigoExiste($data['codigo'] ?? '')) {
            $errores = $v->errors();
            if ($productoModel->codigoExiste($data['codigo'] ?? '')) {
                $errores['codigo'] = 'Ya existe un producto con ese codigo.';
            }
            $this->view('productos/form', [
                'producto' => $data, 'categorias' => $productoModel->categorias(),
                'proveedores' => $productoModel->proveedoresActivos(), 'siguienteCodigo' => $productoModel->siguienteCodigo(), 'errores' => $errores,
            ]);
            return;
        }

        foreach (['codigo', 'codigo_barras', 'proveedor_id', 'fecha_vencimiento', 'marca', 'modelo', 'color', 'material', 'ubicacion', 'lote'] as $campo) {
            if (($data[$campo] ?? '') === '') {
                $data[$campo] = null;
            }
        }
        $data['categoria_id'] = (int) $data['categoria_id'];
        $data['proveedor_id'] = $data['proveedor_id'] !== null ? (int) $data['proveedor_id'] : null;
        $data['stock_minimo'] = (int) ($data['stock_minimo'] ?: 0);

        $stockInicial = (int) Request::input('stock_inicial', 0);

        if ($file = Request::file('imagen')) {
            $data['imagen'] = subir_archivo($file, 'productos');
        }

        $id = $productoModel->create($data);

        if ($stockInicial > 0) {
            $productoModel->registrarMovimiento($id, 'entrada', $stockInicial, 'Existencia inicial', Auth::id());
        }

        $this->flashExito('Producto creado correctamente.');
        $this->redirect('productos/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $productoModel = new Producto();
        $producto = $productoModel->conDetalle($id);
        if (!$producto) {
            $this->flashError('Producto no encontrado.');
            $this->redirect('productos');
            return;
        }
        $kardex = $productoModel->kardex($id);
        $this->view('productos/ver', compact('producto', 'kardex'));
    }

    public function editar(int $id): void
    {
        $productoModel = new Producto();
        $producto = $productoModel->find($id);
        if (!$producto) {
            $this->flashError('Producto no encontrado.');
            $this->redirect('productos');
            return;
        }
        $this->view('productos/form', [
            'producto' => $producto, 'categorias' => $productoModel->categorias(),
            'proveedores' => $productoModel->proveedoresActivos(), 'siguienteCodigo' => $producto['codigo'], 'errores' => [],
        ]);
    }

    public function actualizar(int $id): void
    {
        $data = Request::only(['codigo_barras', 'categoria_id', 'nombre', 'marca', 'modelo', 'color', 'material', 'proveedor_id', 'costo', 'precio', 'stock_minimo', 'ubicacion', 'lote', 'fecha_vencimiento', 'estado']);

        $v = new Validator($data);
        $v->required('nombre')->required('categoria_id')->numeric('costo')->numeric('precio');

        $productoModel = new Producto();
        if ($v->fails()) {
            $this->view('productos/form', [
                'producto' => $data + ['id' => $id], 'categorias' => $productoModel->categorias(),
                'proveedores' => $productoModel->proveedoresActivos(), 'siguienteCodigo' => '', 'errores' => $v->errors(),
            ]);
            return;
        }

        foreach (['codigo_barras', 'proveedor_id', 'fecha_vencimiento', 'marca', 'modelo', 'color', 'material', 'ubicacion', 'lote'] as $campo) {
            if (($data[$campo] ?? '') === '') {
                $data[$campo] = null;
            }
        }
        $data['categoria_id'] = (int) $data['categoria_id'];
        $data['proveedor_id'] = $data['proveedor_id'] !== null ? (int) $data['proveedor_id'] : null;
        $data['stock_minimo'] = (int) ($data['stock_minimo'] ?: 0);

        if ($file = Request::file('imagen')) {
            $ruta = subir_archivo($file, 'productos');
            if ($ruta) {
                $data['imagen'] = $ruta;
            }
        }

        $productoModel->update($id, $data);
        $this->flashExito('Producto actualizado correctamente.');
        $this->redirect('productos/ver/' . $id);
    }

    public function eliminar(int $id): void
    {
        (new Producto())->desactivar($id);
        $this->flashExito('Producto desactivado.');
        $this->redirect('productos');
    }

    public function ajustarInventario(int $id): void
    {
        $cantidad = (int) Request::input('cantidad');
        $tipo = Request::input('tipo'); // entrada | salida | ajuste
        $motivo = Request::input('motivo');

        if ($cantidad === 0 || !in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            $this->flashError('Datos de ajuste invalidos.');
            $this->redirect('productos/ver/' . $id);
            return;
        }

        $cantidadConSigno = $tipo === 'salida' ? -abs($cantidad) : abs($cantidad);
        if ($tipo === 'ajuste') {
            $cantidadConSigno = $cantidad; // el usuario ya indica el signo en un ajuste
        }

        (new Producto())->registrarMovimiento($id, $tipo, $cantidadConSigno, $motivo ?: 'Ajuste manual', Auth::id(), 'ajuste_manual');

        $this->flashExito('Movimiento de inventario registrado.');
        $this->redirect('productos/ver/' . $id);
    }
}
