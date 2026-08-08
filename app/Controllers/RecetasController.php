<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Receta;
use App\Models\Usuario;

class RecetasController extends Controller
{
    public function crear(int $clienteId): void
    {
        $cliente = (new Cliente())->find($clienteId);
        if (!$cliente) {
            $this->flashError('Cliente no encontrado.');
            $this->redirect('clientes');
            return;
        }
        $doctores = (new Usuario())->doctoresActivos();
        $expedienteId = Request::input('expediente_id');
        $this->view('recetas/form', ['cliente' => $cliente, 'doctores' => $doctores, 'expedienteId' => $expedienteId, 'errores' => []]);
    }

    public function guardar(): void
    {
        $clienteId = (int) Request::input('cliente_id');
        $data = Request::only([
            'cliente_id', 'doctor_id', 'expediente_id',
            'od_esfera', 'od_cilindro', 'od_eje', 'od_adicion', 'od_dp',
            'oi_esfera', 'oi_cilindro', 'oi_eje', 'oi_adicion', 'oi_dp',
            'tipo_lente', 'material', 'color', 'tratamiento_lente', 'observaciones',
        ]);

        $v = new Validator($data);
        $v->required('cliente_id')->required('doctor_id');
        if ($v->fails()) {
            $this->flashError('Debe indicar el paciente y el especialista.');
            $this->redirect('recetas/crear/' . $clienteId);
            return;
        }

        foreach ($data as $campo => $valor) {
            if ($valor === '') {
                $data[$campo] = null;
            }
        }
        $data['cliente_id'] = $clienteId;
        $data['doctor_id'] = (int) $data['doctor_id'];
        $data['expediente_id'] = !empty($data['expediente_id']) ? (int) $data['expediente_id'] : null;

        $id = (new Receta())->crear($data);
        $this->flashExito('Receta emitida correctamente.');
        $this->redirect('recetas/ver/' . $id);
    }

    public function ver(int $id): void
    {
        $receta = (new Receta())->obtenerCompleta($id);
        if (!$receta) {
            $this->flashError('Receta no encontrada.');
            $this->redirect('clientes');
            return;
        }
        $this->view('recetas/ver', compact('receta'));
    }

    public function imprimir(int $id): void
    {
        $receta = (new Receta())->obtenerCompleta($id);
        if (!$receta) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        $this->viewRaw('recetas/imprimir', compact('receta') + ['empresa' => Configuracion::empresa()]);
    }

    /**
     * Genera el PDF de la receta. Requiere Dompdf (composer require dompdf/dompdf).
     * Si no esta instalado, redirige a la vista de impresion (Ctrl+P / "Guardar como PDF"
     * del navegador produce un resultado igualmente valido sin dependencias).
     */
    public function pdf(int $id): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $this->flashError('La generacion de PDF en servidor requiere instalar Dompdf (composer require dompdf/dompdf). Mientras tanto, use Imprimir y "Guardar como PDF" desde el navegador.');
            $this->redirect('recetas/imprimir/' . $id);
            return;
        }

        $receta = (new Receta())->obtenerCompleta($id);
        if (!$receta) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        $empresa = Configuracion::empresa();

        ob_start();
        require __DIR__ . '/../Views/recetas/imprimir.php';
        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $dompdf->stream("receta-{$id}.pdf", ['Attachment' => false]);
        exit;
    }

    public function anular(int $id): void
    {
        (new Receta())->anular($id);
        $this->flashExito('Receta anulada.');
        $this->redirect('recetas/ver/' . $id);
    }

    /** Ruta publica (sin login): a donde apunta el QR impreso en la receta */
    public function validarPublico(string $codigo): void
    {
        $receta = (new Receta())->porCodigoValidacion($codigo);
        $this->viewRaw('recetas/validar_publico', ['receta' => $receta]);
    }
}
