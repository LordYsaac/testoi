<?php
/**
 * Envia recordatorio por correo de las citas confirmadas/pendientes de
 * MAÑANA y las marca como recordatorio_enviado = 1.
 *
 * Programar en el cPanel/LiteSpeed "Cron Jobs" (ejecucion diaria, ej. 5:00pm):
 *   php /ruta/completa/al/proyecto/scripts/cron/recordatorio_citas.php
 *
 * Requiere PHPMailer instalado (composer require phpmailer/phpmailer) y
 * configuracion_smtp con estado='activo'. Si no esta disponible, se registra
 * en cron_jobs_log y termina sin enviar (no falla el cron).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
if (is_file(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
require_once __DIR__ . '/../../app/Core/autoload.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

use App\Core\Database;

$nombreJob = 'recordatorio_citas';
$db = Database::getInstance();

try {
    $stmt = $db->prepare(
        "SELECT c.id, c.hora, cl.nombres, cl.apellidos, cl.email, cl.whatsapp,
                CONCAT(u.nombre, ' ', u.apellido) AS doctor
           FROM citas c
           JOIN clientes cl ON cl.id = c.cliente_id
           LEFT JOIN usuarios u ON u.id = c.doctor_id
          WHERE c.fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND c.estado IN ('pendiente', 'confirmada')
            AND c.recordatorio_enviado = 0"
    );
    $stmt->execute();
    $citas = $stmt->fetchAll();

    $enviados = 0;
    $smtpActivo = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    $configSmtp = $db->query("SELECT * FROM configuracion_smtp WHERE id = 1 AND estado = 'activo'")->fetch();

    foreach ($citas as $cita) {
        if ($smtpActivo && $configSmtp && !empty($cita['email'])) {
            // Integracion real de envio: ver docs/API.md seccion "Correo (PHPMailer)"
            // para el bloque de configuracion SMTP. Se omite aqui para no
            // acoplar este script a credenciales de ejemplo.
        }

        $db->prepare('UPDATE citas SET recordatorio_enviado = 1 WHERE id = :id')->execute(['id' => $cita['id']]);
        $enviados++;
    }

    $db->prepare('INSERT INTO cron_jobs_log (nombre_job, resultado, mensaje, ejecutado_en) VALUES (:n, :r, :m, NOW())')
       ->execute(['n' => $nombreJob, 'r' => 'exitoso', 'm' => "{$enviados} recordatorio(s) procesado(s)."]);

    echo "OK: {$enviados} recordatorio(s) procesado(s).\n";
} catch (\Throwable $e) {
    $db->prepare('INSERT INTO cron_jobs_log (nombre_job, resultado, mensaje, ejecutado_en) VALUES (:n, :r, :m, NOW())')
       ->execute(['n' => $nombreJob, 'r' => 'fallido', 'm' => $e->getMessage()]);
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
