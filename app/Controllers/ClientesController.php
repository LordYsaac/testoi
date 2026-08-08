<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\ExpedienteClinico;
use App\Models\Receta;

class ClientesController extends Controller
{
    public function index(): void
    {
        $busqueda = Request::input('q', '');
        $estado = Request::input('estado', '');
        $pagina = max(1, (int) Request::input('pagina', 1));
        $porPagina = 20;

        $clienteModel = new Cliente();
        $clientes = $clienteModel->listar($busqueda, $estado, $porPagina, $pagina);
        $total = $clienteModel->totalListar($busqueda, $estado);
        $totalPaginas = (int) ceil($total / $porPagina);

        $this->view('clientes/index', compact('clientes', 'busqueda', 'estado', 'pagina', 'totalPaginas', 'total'));
    }

    public function buscarAjax(): void
    {
        $q = Request::input('q', '');
        $clientes = (new Cliente())->listar($q, '', 10, 1);
        $this->json($clientes);
    }

    public function crear(): void
    {
        $seguros = (new Cliente())->segurosMedicos();
        $this->view('clientes/form', ['cliente' => null, 'seguros' => $seguros, 'errores' => []]);
    }

    public function guardar(): void
    {
        $data = Request::only(['nombres', 'apellidos', 'sexo', 'fecha_nacimiento', 'telefono', 'whatsapp', 'email', 'direccion', 'cedula_pasaporte', 'seguro_medico_id', 'observaciones']);

        $v = new Validator($data);
        $v->required('nombres')->required('apellidos')->email('email')->date('fecha_nacimiento');

        $clienteModel = new Cliente();
        if ($v->fails() || $clienteModel->cedulaExiste($data['cedula_pasaporte'] ?? '')) {
            $errores = $v->errors();
            if ($clienteModel->cedulaExiste($data['cedula_pasaporte'] ?? '')) {
                $errores['cedula_pasaporte'] = 'Ya existe un cliente con esa cedula/pasaporte.';
            }
            $seguros = $clienteModel->segurosMedicos();
            $this->view('clientes/form', ['cliente' => $data, 'seguros' => $seguros, 'errores' => $errores]);
            return;
        }

        $data['fecha_nacimiento'] = $data['fecha_nacimiento'] !== '' ? $data['fecha_nacimiento'] : null;
        $data['cedula_pasaporte'] = $data['cedula_pasaporte'] !== '' ? $data['cedula_pasaporte'] : null;
        $data['seguro_medico_id'] = !empty($data['seguro_medico_id']) ? (int) $data['seguro_medico_id'] : null;
        $data['created_by'] = Auth::id();

        if ($file = Request::file('foto')) {
            $data['foto'] = subir_archivo($file, 'clientes');
        }

        $id = $clienteModel->create($data);
        $this->flashExito('Cliente registrado correctamente.');
        $this->redirect('clientes/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $clienteModel = new Cliente();
        $cliente = $clienteModel->conDetalle($id);
        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('clientes');
            return;
        }

        $antecedentes = $clienteModel->obtenerAntecedentes($id);
        $historialClinico = (new ExpedienteClinico())->porCliente($id);
        $recetas = (new Receta())->porCliente($id);

        $this->view('clientes/ver', compact('cliente', 'antecedentes', 'historialClinico', 'recetas'));
    }

    public function editar(int $id): void
    {
        $clienteModel = new Cliente();
        $cliente = $clienteModel->find($id);
        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $seguros = $clienteModel->segurosMedicos();
        $this->view('clientes/form', ['cliente' => $cliente, 'seguros' => $seguros, 'errores' => []]);
    }

    public function actualizar(int $id): void
    {
        $data = Request::only(['nombres', 'apellidos', 'sexo', 'fecha_nacimiento', 'telefono', 'whatsapp', 'email', 'direccion', 'cedula_pasaporte', 'seguro_medico_id', 'observaciones', 'estado']);

        $v = new Validator($data);
        $v->required('nombres')->required('apellidos')->email('email');

        $clienteModel = new Cliente();
        if ($v->fails() || $clienteModel->cedulaExiste($data['cedula_pasaporte'] ?? '', $id)) {
            $errores = $v->errors();
            if ($clienteModel->cedulaExiste($data['cedula_pasaporte'] ?? '', $id)) {
                $errores['cedula_pasaporte'] = 'Ya existe otro cliente con esa cedula/pasaporte.';
            }
            $seguros = $clienteModel->segurosMedicos();
            $this->view('clientes/form', ['cliente' => $data + ['id' => $id], 'seguros' => $seguros, 'errores' => $errores]);
            return;
        }

        $data['fecha_nacimiento'] = $data['fecha_nacimiento'] !== '' ? $data['fecha_nacimiento'] : null;
        $data['cedula_pasaporte'] = $data['cedula_pasaporte'] !== '' ? $data['cedula_pasaporte'] : null;
        $data['seguro_medico_id'] = !empty($data['seguro_medico_id']) ? (int) $data['seguro_medico_id'] : null;

        if ($file = Request::file('foto')) {
            $ruta = subir_archivo($file, 'clientes');
            if ($ruta) {
                $data['foto'] = $ruta;
            }
        }

        $clienteModel->update($id, $data);
        $this->flashExito('Cliente actualizado correctamente.');
        $this->redirect('clientes/ver/' . $id);
    }

    public function eliminar(int $id): void
    {
        (new Cliente())->desactivar($id);
        $this->flashExito('Cliente desactivado.');
        $this->redirect('clientes');
    }

    public function guardarAntecedentes(int $id): void
    {
        $data = Request::only(['familiares', 'personales', 'quirurgicos', 'alergias', 'medicamentos']);
        (new Cliente())->guardarAntecedentes($id, $data, Auth::id());
        $this->flashExito('Antecedentes actualizados.');
        $this->redirect('clientes/ver/' . $id);
    }
}
