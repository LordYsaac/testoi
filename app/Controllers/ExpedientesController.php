<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\ExpedienteClinico;
use App\Models\Usuario;

class ExpedientesController extends Controller
{
    private const SECCIONES = ['agudeza_visual', 'refraccion', 'distancia_pupilar', 'queratometria', 'tonometria', 'biomicroscopia', 'fondo_ojo'];

    public function porCliente(int $clienteId): void
    {
        $cliente = (new Cliente())->find($clienteId);
        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $historial = (new ExpedienteClinico())->porCliente($clienteId);
        $this->view('expedientes/listado', compact('cliente', 'historial'));
    }

    public function crear(int $clienteId): void
    {
        $cliente = (new Cliente())->find($clienteId);
        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $antecedentes = (new Cliente())->obtenerAntecedentes($clienteId);
        $doctores = (new Usuario())->doctoresActivos();
        $this->view('expedientes/form', ['cliente' => $cliente, 'expediente' => null, 'antecedentes' => $antecedentes, 'doctores' => $doctores, 'errores' => []]);
    }

    public function guardar(): void
    {
        $clienteId = (int) Request::input('cliente_id');
        $encabezado = [
            'cliente_id'      => $clienteId,
            'doctor_id'       => Request::input('doctor_id') ?: null,
            'motivo_consulta' => Request::input('motivo_consulta'),
            'indicaciones'    => Request::input('indicaciones'),
            'observaciones'   => Request::input('observaciones'),
            'proxima_cita'    => Request::input('proxima_cita') ?: null,
        ];

        $v = new Validator($encabezado);
        $v->required('cliente_id')->required('motivo_consulta');
        if ($v->fails()) {
            $this->flashError('El motivo de consulta es obligatorio.');
            $this->redirect('expedientes/crear/' . $clienteId);
            return;
        }

        $secciones = $this->extraerSecciones();
        $diagnosticos = array_filter((array) Request::input('diagnosticos', []));
        $tratamientos = array_filter((array) Request::input('tratamientos', []));

        $id = (new ExpedienteClinico())->crearCompleto($encabezado, $secciones, $diagnosticos, $tratamientos);

        $this->flashExito('Entrada de historial clinico guardada correctamente.');
        $this->redirect('expedientes/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $expediente = (new ExpedienteClinico())->obtenerCompleto($id);
        if (!$expediente) {
            $this->flashError('Expediente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $this->view('expedientes/ver', compact('expediente'));
    }

    public function editar(int $id): void
    {
        $expediente = (new ExpedienteClinico())->obtenerCompleto($id);
        if (!$expediente) {
            $this->flashError('Expediente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $cliente = (new Cliente())->find((int) $expediente['cliente_id']);
        $doctores = (new Usuario())->doctoresActivos();
        $this->view('expedientes/form', ['cliente' => $cliente, 'expediente' => $expediente, 'antecedentes' => null, 'doctores' => $doctores, 'errores' => []]);
    }

    public function actualizar(int $id): void
    {
        $encabezado = [
            'doctor_id'       => Request::input('doctor_id') ?: null,
            'motivo_consulta' => Request::input('motivo_consulta'),
            'indicaciones'    => Request::input('indicaciones'),
            'observaciones'   => Request::input('observaciones'),
            'proxima_cita'    => Request::input('proxima_cita') ?: null,
        ];

        $secciones = $this->extraerSecciones();
        $diagnosticos = array_filter((array) Request::input('diagnosticos', []));
        $tratamientos = array_filter((array) Request::input('tratamientos', []));

        (new ExpedienteClinico())->actualizarCompleto($id, $encabezado, $secciones, $diagnosticos, $tratamientos);

        $this->flashExito('Historial clinico actualizado.');
        $this->redirect('expedientes/ver/' . $id);
    }

    public function subirAdjunto(int $id): void
    {
        $file = Request::file('adjunto');
        if (!$file) {
            $this->flashError('Debe seleccionar un archivo.');
            $this->redirect('expedientes/ver/' . $id);
            return;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tipo = $extension === 'pdf' ? 'pdf' : (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? 'imagen' : 'estudio');
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        $ruta = subir_archivo($file, 'expedientes', $extensionesPermitidas);
        if (!$ruta) {
            $this->flashError('No se pudo subir el archivo (formato no permitido o error de subida).');
            $this->redirect('expedientes/ver/' . $id);
            return;
        }

        (new ExpedienteClinico())->agregarAdjunto($id, $tipo, $file['name'], $ruta, Request::input('descripcion'), Auth::id());
        $this->flashExito('Archivo adjuntado correctamente.');
        $this->redirect('expedientes/ver/' . $id);
    }

    public function eliminarAdjunto(int $id): void
    {
        // $id aqui es el id del adjunto; se resuelve el expediente para redirigir
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare('SELECT expediente_id, ruta_archivo FROM expediente_adjuntos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $adjunto = $stmt->fetch();

        if ($adjunto) {
            $db->prepare('DELETE FROM expediente_adjuntos WHERE id = :id')->execute(['id' => $id]);
            $rutaFisica = __DIR__ . '/../../storage/uploads/' . $adjunto['ruta_archivo'];
            if (is_file($rutaFisica)) {
                @unlink($rutaFisica);
            }
            $this->flashExito('Adjunto eliminado.');
            $this->redirect('expedientes/ver/' . $adjunto['expediente_id']);
            return;
        }

        $this->redirect('clientes');
    }

    private function extraerSecciones(): array
    {
        $secciones = [];
        foreach (self::SECCIONES as $seccion) {
            $secciones[$seccion] = Request::input($seccion, []);
        }
        return $secciones;
    }
}
