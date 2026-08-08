<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\CajaSesion;

class CajaController extends Controller
{
    public function index(): void
    {
        $cajaModel = new CajaSesion();
        $sesionAbierta = $cajaModel->sesionAbiertaDe(Auth::id());
        $resumen = $sesionAbierta ? $cajaModel->resumen((int) $sesionAbierta['id']) : null;
        $movimientos = $sesionAbierta ? $cajaModel->movimientos((int) $sesionAbierta['id']) : [];
        $historial = $cajaModel->historial(10);
        $this->view('caja/index', compact('sesionAbierta', 'resumen', 'movimientos', 'historial'));
    }

    public function abrir(): void
    {
        $montoApertura = (float) Request::input('monto_apertura', 0);
        $observaciones = Request::input('observaciones');

        try {
            (new CajaSesion())->abrir(Auth::id(), $montoApertura, $observaciones);
            $this->flashExito('Caja abierta correctamente.');
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('caja');
    }

    public function cerrar(): void
    {
        $montoDeclarado = (float) Request::input('monto_declarado', 0);
        $cajaModel = new CajaSesion();
        $sesion = $cajaModel->sesionAbiertaDe(Auth::id());

        if (!$sesion) {
            $this->flashError('No tiene una caja abierta.');
            $this->redirect('caja');
            return;
        }

        $diferencia = $cajaModel->cerrar((int) $sesion['id'], $montoDeclarado);
        $mensaje = abs($diferencia) < 0.01
            ? 'Caja cerrada correctamente, sin diferencias.'
            : sprintf('Caja cerrada con una diferencia de %s%.2f respecto a lo esperado.', $diferencia > 0 ? '+' : '', $diferencia);
        $this->flashExito($mensaje);
        $this->redirect('caja');
    }

    public function registrarMovimiento(): void
    {
        $tipo = Request::input('tipo'); // ingreso | egreso
        $concepto = Request::input('concepto');
        $monto = (float) Request::input('monto', 0);

        $cajaModel = new CajaSesion();
        $sesion = $cajaModel->sesionAbiertaDe(Auth::id());

        if (!$sesion || $monto <= 0 || !in_array($tipo, ['ingreso', 'egreso'], true)) {
            $this->flashError('No se pudo registrar el movimiento. Verifique que tenga una caja abierta y datos validos.');
            $this->redirect('caja');
            return;
        }

        $cajaModel->registrarMovimiento((int) $sesion['id'], $tipo, $concepto ?: ucfirst($tipo), $monto, 'manual', null, Auth::id());
        $this->flashExito('Movimiento registrado.');
        $this->redirect('caja');
    }
}
