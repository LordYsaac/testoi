<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Usuario;

class CitasController extends Controller
{
    public function index(): void
    {
        $anio = (int) Request::input('anio', date('Y'));
        $mes = (int) Request::input('mes', date('n'));

        if ($mes < 1) { $mes = 12; $anio--; }
        if ($mes > 12) { $mes = 1; $anio++; }

        $conteos = (new Cita())->conteoPorMes($anio, $mes);
        $this->view('citas/index', compact('anio', 'mes', 'conteos'));
    }

    public function dia(string $fecha): void
    {
        if (!\DateTime::createFromFormat('Y-m-d', $fecha)) {
            $fecha = date('Y-m-d');
        }
        $citas = (new Cita())->porFecha($fecha);
        $doctores = (new Usuario())->doctoresActivos();
        $this->view('citas/dia', compact('fecha', 'citas', 'doctores'));
    }

    public function crear(): void
    {
        $fecha = Request::input('fecha', date('Y-m-d'));
        $clienteId = (int) Request::input('cliente_id', 0);
        $cliente = $clienteId ? (new Cliente())->find($clienteId) : null;
        $doctores = (new Usuario())->doctoresActivos();
        $this->view('citas/form', ['cita' => null, 'fecha' => $fecha, 'cliente' => $cliente, 'doctores' => $doctores, 'errores' => []]);
    }

    public function guardar(): void
    {
        $data = Request::only(['cliente_id', 'doctor_id', 'fecha', 'hora', 'motivo', 'notas']);

        $v = new Validator($data);
        $v->required('cliente_id')->required('fecha')->required('hora');
        if ($v->fails()) {
            $this->flashError('Debe indicar paciente, fecha y hora.');
            $this->redirect('citas/crear?fecha=' . $data['fecha']);
            return;
        }

        $citaModel = new Cita();
        $data['cliente_id'] = (int) $data['cliente_id'];
        $data['doctor_id'] = !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null;
        $data['created_by'] = Auth::id();
        $data['estado'] = 'pendiente';

        if ($citaModel->hayConflicto($data['doctor_id'], $data['fecha'], $data['hora'])) {
            $this->flashError('Ese doctor ya tiene una cita agendada a esa hora. Verifique antes de continuar.');
        }

        $citaModel->create($data);
        $this->flashExito('Cita agendada correctamente.');
        $this->redirect('citas/dia/' . $data['fecha']);
    }

    public function editar(int $id): void
    {
        $citaModel = new Cita();
        $cita = $citaModel->conDetalle($id);
        if (!$cita) {
            $this->flashError('Cita no encontrada.');
            $this->redirect('citas');
            return;
        }
        $doctores = (new Usuario())->doctoresActivos();
        $this->view('citas/form', ['cita' => $cita, 'fecha' => $cita['fecha'], 'cliente' => null, 'doctores' => $doctores, 'errores' => []]);
    }

    public function actualizar(int $id): void
    {
        $data = Request::only(['doctor_id', 'fecha', 'hora', 'motivo', 'notas', 'estado']);
        $data['doctor_id'] = !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null;

        (new Cita())->update($id, $data);
        $this->flashExito('Cita actualizada.');
        $this->redirect('citas/dia/' . $data['fecha']);
    }

    public function cambiarEstado(int $id): void
    {
        $estado = Request::input('estado');
        $cita = (new Cita())->find($id);
        if ($cita && in_array($estado, ['pendiente', 'confirmada', 'cancelada', 'finalizada'], true)) {
            (new Cita())->cambiarEstado($id, $estado);
            $this->flashExito('Estado de la cita actualizado.');
        }
        $this->redirect('citas/dia/' . ($cita['fecha'] ?? date('Y-m-d')));
    }
}
