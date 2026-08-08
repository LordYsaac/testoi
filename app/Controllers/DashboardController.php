<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        $kpis = $db->query('SELECT * FROM vista_dashboard_kpis')->fetch();

        $stockBajo = $db->query('SELECT * FROM vista_stock_bajo LIMIT 5')->fetchAll();
        $citasHoy = $db->query('SELECT * FROM vista_citas_dia LIMIT 8')->fetchAll();
        $clientesMorosos = $db->query('SELECT * FROM vista_clientes_morosos ORDER BY dias_vencido DESC LIMIT 5')->fetchAll();

        $ventasUltimos7Dias = $db->query(
            "SELECT DATE(fecha) AS dia, SUM(total) AS total
               FROM facturas
              WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado <> 'anulada'
              GROUP BY DATE(fecha)
              ORDER BY dia"
        )->fetchAll();

        $stmt = $db->prepare(
            "SELECT id, tipo, titulo, mensaje, enlace, created_at
               FROM notificaciones
              WHERE leida = 0 AND (usuario_id = :uid OR rol_id = :rid)
              ORDER BY created_at DESC LIMIT 8"
        );
        $stmt->execute(['uid' => Auth::id(), 'rid' => $_SESSION['rol_id'] ?? 0]);
        $notificaciones = $stmt->fetchAll();

        $this->view('dashboard/index', compact('kpis', 'stockBajo', 'citasHoy', 'clientesMorosos', 'ventasUltimos7Dias', 'notificaciones'));
    }

    public function marcarNotificacionLeida(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE notificaciones SET leida = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $this->json(['ok' => true]);
    }
}
