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

        if (!empty($resultado['requiere_2fa'])) {
            $this->redirect('verificar-2fa');
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

    public function mostrarVerificar2FA(): void
    {
        if (!Auth::hayDosFactorPendiente()) {
            $this->redirect('login');
            return;
        }
        $this->viewRaw('auth/verificar_2fa', ['error' => null]);
    }

    public function verificar2FA(): void
    {
        if (!Auth::hayDosFactorPendiente()) {
            $this->redirect('login');
            return;
        }

        $codigo = Request::input('codigo', '');
        $resultado = Auth::completarDosFactor($codigo);

        if (!$resultado['ok']) {
            $this->viewRaw('auth/verificar_2fa', ['error' => $resultado['mensaje']]);
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

    public function mostrarSeguridad(): void
    {
        $usuario = (new Usuario())->find(Auth::id());
        $this->view('auth/seguridad', ['usuario' => $usuario, 'qrUrl' => null, 'secretoTemporal' => null, 'error' => null]);
    }

    /** Genera un secreto TOTP temporal (aun no activo) y muestra el QR para escanear */
    public function generar2FA(): void
    {
        $usuario = (new Usuario())->find(Auth::id());
        $secreto = \App\Core\Totp::generarSecreto();
        $_SESSION['2fa_secreto_temporal'] = $secreto;

        $uri = \App\Core\Totp::otpauthUri($secreto, $usuario['username'], APP_NAME);
        $qrUrl = \App\Core\QrCode::urlImagen($uri, 220);

        $this->view('auth/seguridad', ['usuario' => $usuario, 'qrUrl' => $qrUrl, 'secretoTemporal' => $secreto, 'error' => null]);
    }

    /** Confirma el codigo generado por la app autenticadora y activa 2FA definitivamente */
    public function activar2FA(): void
    {
        $codigo = Request::input('codigo', '');
        $secreto = $_SESSION['2fa_secreto_temporal'] ?? null;

        if (!$secreto) {
            $this->flashError('La configuracion expiro. Genere el codigo QR de nuevo.');
            $this->redirect('seguridad');
            return;
        }

        if (!\App\Core\Totp::verificar($secreto, $codigo)) {
            $usuario = (new Usuario())->find(Auth::id());
            $uri = \App\Core\Totp::otpauthUri($secreto, $usuario['username'], APP_NAME);
            $qrUrl = \App\Core\QrCode::urlImagen($uri, 220);
            $this->view('auth/seguridad', ['usuario' => $usuario, 'qrUrl' => $qrUrl, 'secretoTemporal' => $secreto, 'error' => 'El codigo no es correcto. Verifique la hora de su telefono e intente de nuevo.']);
            return;
        }

        (new Usuario())->update(Auth::id(), ['two_factor_secret' => $secreto, 'two_factor_activo' => 1]);
        unset($_SESSION['2fa_secreto_temporal']);

        $this->flashExito('Verificacion en dos pasos activada correctamente.');
        $this->redirect('seguridad');
    }

    public function desactivar2FA(): void
    {
        $password = Request::input('password_actual', '');
        $usuario = (new Usuario())->find(Auth::id());

        if (!password_verify($password, $usuario['password_hash'])) {
            $this->view('auth/seguridad', ['usuario' => $usuario, 'qrUrl' => null, 'secretoTemporal' => null, 'error' => 'Contraseña incorrecta.']);
            return;
        }

        (new Usuario())->update(Auth::id(), ['two_factor_secret' => null, 'two_factor_activo' => 0]);
        $this->flashExito('Verificacion en dos pasos desactivada.');
        $this->redirect('seguridad');
    }
}
