<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function mostrarLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->viewRaw('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        $username = Request::input('username');
        $password = Request::input('password');

        $v = new Validator(['username' => $username, 'password' => $password]);
        $v->required('username', 'usuario')->required('password', 'contraseña');

        if ($v->fails()) {
            $this->viewRaw('auth/login', ['error' => 'Debe ingresar usuario y contraseña.']);
            return;
        }

        $resultado = Auth::intentar($username, $password);

        if (!$resultado['ok']) {
            $this->viewRaw('auth/login', ['error' => $resultado['mensaje']]);
            return;
        }

        if ($resultado['debe_cambiar_password']) {
            $this->redirect('cambiar-password');
            return;
        }

        $destino = $_SESSION['url_intentada'] ?? null;
        unset($_SESSION['url_intentada']);
        $this->redirect($destino && !str_contains($destino, 'login') ? ltrim($destino, '/') : 'dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('login');
    }

    public function mostrarCambiarPassword(): void
    {
        $this->view('auth/cambiar_password', ['error' => null]);
    }

    public function cambiarPassword(): void
    {
        $actual = Request::input('password_actual');
        $nueva = Request::input('password_nueva');
        $confirmacion = Request::input('password_confirmacion');

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->find(Auth::id());

        if (!password_verify($actual, $usuario['password_hash'])) {
            $this->view('auth/cambiar_password', ['error' => 'La contraseña actual no es correcta.']);
            return;
        }
        if (strlen($nueva) < 8) {
            $this->view('auth/cambiar_password', ['error' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
            return;
        }
        if ($nueva !== $confirmacion) {
            $this->view('auth/cambiar_password', ['error' => 'La confirmacion no coincide con la nueva contraseña.']);
            return;
        }

        $usuarioModel->update(Auth::id(), [
            'password_hash'         => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar_password' => 0,
        ]);

        $this->flashExito('Contraseña actualizada correctamente.');
        $this->redirect('dashboard');
    }
}
