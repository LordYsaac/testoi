<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Proveedor;

class ProveedoresController extends Controller
{
    public function index(): void
    {
        $busqueda = Request::input('q', '');
        $proveedores = (new Proveedor())->listar($busqueda);
        $this->view('proveedores/index', compact('proveedores', 'busqueda'));
    }

    public function crear(): void
    {
        $this->view('proveedores/form', ['proveedor' => null, 'errores' => []]);
    }

    public function guardar(): void
    {
        $data = Request::only(['nombre', 'contacto_nombre', 'telefono', 'email', 'direccion', 'rnc']);
        $v = new Validator($data);
        $v->required('nombre')->email('email');

        if ($v->fails()) {
            $this->view('proveedores/form', ['proveedor' => $data, 'errores' => $v->errors()]);
            return;
        }

        $id = (new Proveedor())->create($data);
        $this->flashExito('Proveedor registrado correctamente.');
        $this->redirect('proveedores/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $proveedorModel = new Proveedor();
        $proveedor = $proveedorModel->find($id);
        if (!$proveedor) {
            $this->flashError('Proveedor no encontrado.');
            $this->redirect('proveedores');
            return;
        }
        $historial = $proveedorModel->historialCompras($id);
        $estadoCuenta = $proveedorModel->estadoCuenta($id);
        $this->view('proveedores/ver', compact('proveedor', 'historial', 'estadoCuenta'));
    }

    public function editar(int $id): void
    {
        $proveedor = (new Proveedor())->find($id);
        if (!$proveedor) {
            $this->flashError('Proveedor no encontrado.');
            $this->redirect('proveedores');
            return;
        }
        $this->view('proveedores/form', compact('proveedor') + ['errores' => []]);
    }

    public function actualizar(int $id): void
    {
        $data = Request::only(['nombre', 'contacto_nombre', 'telefono', 'email', 'direccion', 'rnc', 'estado']);
        $v = new Validator($data);
        $v->required('nombre')->email('email');

        if ($v->fails()) {
            $this->view('proveedores/form', ['proveedor' => $data + ['id' => $id], 'errores' => $v->errors()]);
            return;
        }

        (new Proveedor())->update($id, $data);
        $this->flashExito('Proveedor actualizado correctamente.');
        $this->redirect('proveedores/ver/' . $id);
    }

    public function eliminar(int $id): void
    {
        (new Proveedor())->desactivar($id);
        $this->flashExito('Proveedor desactivado.');
        $this->redirect('proveedores');
    }
}
