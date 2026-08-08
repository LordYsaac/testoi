<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Factura;

class CuentasPorCobrarController extends Controller
{
    public function index(): void
    {
        $morosos = Database::getInstance()->query('SELECT * FROM vista_clientes_morosos ORDER BY dias_vencido DESC')->fetchAll();
        $this->view('cuentas_cobrar/index', compact('morosos'));
    }

    public function cliente(int $clienteId): void
    {
        $pendientes = (new Factura())->pendientesDeCliente($clienteId);
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT nombres, apellidos, codigo_cliente FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $clienteId]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('cuentas-por-cobrar');
            return;
        }

        $this->view('cuentas_cobrar/cliente', compact('cliente', 'pendientes', 'clienteId'));
    }

    public function abonar(int $facturaId): void
    {
        $monto = (float) Request::input('monto', 0);
        $clienteId = (int) Request::input('cliente_id');

        if ($monto <= 0) {
            $this->flashError('El monto del abono debe ser mayor a cero.');
            $this->redirect('cuentas-por-cobrar/cliente/' . $clienteId);
            return;
        }

        (new Factura())->registrarAbono($facturaId, $monto, Auth::id());
        $this->flashExito('Abono registrado correctamente.');
        $this->redirect('cuentas-por-cobrar/cliente/' . $clienteId);
    }
}
