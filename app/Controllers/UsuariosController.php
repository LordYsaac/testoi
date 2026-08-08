<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Rol;
use App\Models\Usuario;

class UsuariosController extends Controller
{
    public function index(): void
    {
        $usuarios = (new Usuario())->conRol();
        $this->view('usuarios/index', compact('usuarios'));
    }

    public function crear(): void
    {
        $roles = (new Rol())->all('nombre');
        $siguienteCodigo = (new Usuario())->siguienteCodigo();
        $this->view('usuarios/form', ['usuario' => null, 'roles' => $roles, 'siguienteCodigo' => $siguienteCodigo, 'errores' => []]);
    }

    public function guardar(): void
    {
        $data = Request::only(['nombre', 'apellido', 'email', 'username', 'telefono', 'rol_id', 'cmd_colegiado']);
        $password = Request::input('password');

        $usuarioModel = new Usuario();
        $v = new Validator($data + ['password' => $password]);
        $v->required('nombre')->required('apellido')->required('email')->email('email')
          ->required('username')->min('username', 4)->required('rol_id')->required('password')->min('password', 8);

        if ($v->fails() || $usuarioModel->emailExiste($data['email']) || $usuarioModel->usernameExiste($data['username'])) {
            $errores = $v->errors();
            if ($usuarioModel->emailExiste($data['email'])) {
                $errores['email'] = 'Ya existe un usuario con ese correo.';
            }
            if ($usuarioModel->usernameExiste($data['username'])) {
                $errores['username'] = 'Ese nombre de usuario ya esta en uso.';
            }
            $roles = (new Rol())->all('nombre');
            $this->view('usuarios/form', ['usuario' => $data, 'roles' => $roles, 'siguienteCodigo' => $usuarioModel->siguienteCodigo(), 'errores' => $errores]);
            return;
        }

        $foto = null;
        if ($file = Request::file('foto')) {
            $foto = subir_archivo($file, 'clientes'); // se reutiliza la misma carpeta de fotos de personas
        }

        $data['codigo'] = $usuarioModel->siguienteCodigo();
        $data['rol_id'] = (int) $data['rol_id'];
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $data['foto'] = $foto;
        $data['debe_cambiar_password'] = 1;

        $usuarioModel->create($data);
        $this->flashExito('Usuario creado correctamente.');
        $this->redirect('usuarios');
    }

    public function editar(int $id): void
    {
        $usuario = (new Usuario())->find($id);
        if (!$usuario) {
            $this->flashError('Usuario no encontrado.');
            $this->redirect('usuarios');
            return;
        }
        $roles = (new Rol())->all('nombre');
        $this->view('usuarios/form', ['usuario' => $usuario, 'roles' => $roles, 'siguienteCodigo' => $usuario['codigo'], 'errores' => []]);
    }

    public function actualizar(int $id): void
    {
        $data = Request::only(['nombre', 'apellido', 'email', 'telefono', 'rol_id', 'cmd_colegiado', 'estado']);
        $usuarioModel = new Usuario();

        $v = new Validator($data);
        $v->required('nombre')->required('apellido')->required('email')->email('email')->required('rol_id');

        if ($v->fails() || $usuarioModel->emailExiste($data['email'], $id)) {
            $errores = $v->errors();
            if ($usuarioModel->emailExiste($data['email'], $id)) {
                $errores['email'] = 'Ya existe otro usuario con ese correo.';
            }
            $roles = (new Rol())->all('nombre');
            $this->view('usuarios/form', ['usuario' => $data + ['id' => $id], 'roles' => $roles, 'siguienteCodigo' => '', 'errores' => $errores]);
            return;
        }

        if ($file = Request::file('foto')) {
            $ruta = subir_archivo($file, 'clientes');
            if ($ruta) {
                $data['foto'] = $ruta;
            }
        }

        $data['rol_id'] = (int) $data['rol_id'];
        $usuarioModel->update($id, $data);
        $this->flashExito('Usuario actualizado correctamente.');
        $this->redirect('usuarios');
    }

    public function eliminar(int $id): void
    {
        if ($id === Auth::id()) {
            $this->flashError('No puede desactivar su propio usuario.');
            $this->redirect('usuarios');
            return;
        }
        (new Usuario())->update($id, ['estado' => 'inactivo']);
        $this->flashExito('Usuario desactivado.');
        $this->redirect('usuarios');
    }

    public function restablecerPassword(int $id): void
    {
        $temporal = substr(bin2hex(random_bytes(6)), 0, 10);
        (new Usuario())->update($id, [
            'password_hash'         => password_hash($temporal, PASSWORD_DEFAULT),
            'debe_cambiar_password' => 1,
            'intentos_fallidos'     => 0,
            'bloqueado_hasta'       => null,
        ]);
        $this->flashExito("Contraseña temporal generada: {$temporal} (el usuario debera cambiarla al iniciar sesion).");
        $this->redirect('usuarios');
    }
}
