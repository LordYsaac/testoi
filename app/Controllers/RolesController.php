<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Rol;

class RolesController extends Controller
{
    public function index(): void
    {
        $roles = (new Rol())->todosConConteoUsuarios();
        $this->view('roles/index', compact('roles'));
    }

    public function crear(): void
    {
        $this->view('roles/crear', ['errores' => []]);
    }

    public function guardar(): void
    {
        $nombre = Request::input('nombre');
        $descripcion = Request::input('descripcion');

        if (trim($nombre) === '') {
            $this->view('roles/crear', ['errores' => ['nombre' => 'El nombre del rol es obligatorio.']]);
            return;
        }

        $rolModel = new Rol();
        $id = $rolModel->create(['nombre' => $nombre, 'descripcion' => $descripcion, 'es_sistema' => 0]);
        $this->flashExito('Rol creado. Ahora asigne sus permisos.');
        $this->redirect('roles/editar/' . $id);
    }

    public function editar(int $id): void
    {
        $rolModel = new Rol();
        $rol = $rolModel->find($id);
        if (!$rol) {
            $this->flashError('Rol no encontrado.');
            $this->redirect('roles');
            return;
        }
        $matriz = $rolModel->matrizPermisos($id);
        $this->view('roles/editar', compact('rol', 'matriz'));
    }

    public function actualizar(int $id): void
    {
        $rolModel = new Rol();
        $rol = $rolModel->find($id);
        if (!$rol) {
            $this->flashError('Rol no encontrado.');
            $this->redirect('roles');
            return;
        }

        $nombre = Request::input('nombre');
        $descripcion = Request::input('descripcion');
        $permisoIds = $_POST['permisos'] ?? [];

        $rolModel->update($id, ['nombre' => $nombre, 'descripcion' => $descripcion]);
        $rolModel->sincronizarPermisos($id, array_map('intval', $permisoIds));

        $this->flashExito('Permisos actualizados. Los usuarios con este rol deberan volver a iniciar sesion para verlos reflejados.');
        $this->redirect('roles');
    }
}
