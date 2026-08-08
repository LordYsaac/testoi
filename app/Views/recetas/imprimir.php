<?php
use App\Core\QrCode;

$qrUrl = QrCode::urlImagen(QrCode::urlValidacionReceta($receta['codigo_validacion']), 150);
$nombreEmpresa = $empresa['nombre_comercial'] ?? $empresa['nombre_empresa'] ?? 'Optica';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Receta #<?= (int) $receta['id'] ?></title>
<style>
    body { font-family: "DejaVu Sans", Helvetica, Arial, sans-serif; color: #16232b; font-size: 13px; }
    .print-toolbar { text-align: right; padding: 10px 0; }
    .hoja { max-width: 720px; margin: 0 auto; padding: 28px; border: 1px solid #d8e2e2; }
    .encabezado { width: 100%; border-bottom: 3px solid #0e6e6e; padding-bottom: 12px; margin-bottom: 16px; }
    .encabezado td { vertical-align: top; }
    .nombre-empresa { font-size: 19px; font-weight: bold; color: #0a4f4f; }
    .datos-empresa { font-size: 11px; color: #52696f; line-height: 1.5; }
    .titulo-doc { text-align: right; font-size: 15px; font-weight: bold; color: #0e6e6e; text-transform: uppercase; letter-spacing: 1px; }
    .folio { text-align: right; font-size: 11px; color: #52696f; }
    .datos-paciente { width: 100%; margin-bottom: 14px; font-size: 12px; }
    .datos-paciente td { padding: 3px 0; }
    .etiqueta { color: #52696f; }
    .tabla-graduacion { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
    .tabla-graduacion th, .tabla-graduacion td { border: 1px solid #d8e2e2; padding: 7px 10px; text-align: center; font-size: 12.5px; }
    .tabla-graduacion th { background: #e6f2f2; color: #0a4f4f; }
    .tabla-graduacion td:first-child, .tabla-graduacion th:first-child { text-align: left; background: #f5f8f8; font-weight: bold; }
    .especificaciones { width: 100%; margin-bottom: 16px; font-size: 12.5px; }
    .especificaciones td { padding: 4px 0; }
    .pie { width: 100%; margin-top: 30px; }
    .firma-linea { border-top: 1px solid #16232b; width: 220px; margin-top: 40px; padding-top: 4px; font-size: 11px; text-align: center; }
    .qr-box { text-align: center; font-size: 9px; color: #52696f; }
    .qr-box img { width: 90px; height: 90px; }
    .codigo-validacion { font-family: monospace; font-size: 9px; color: #8199a0; }
    @media print { .print-toolbar { display: none; } .hoja { border: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="print-toolbar no-print">
        <button onclick="window.print()" style="padding:8px 16px;background:#0e6e6e;color:#fff;border:none;border-radius:6px;cursor:pointer;">Imprimir / Guardar como PDF</button>
    </div>

    <div class="hoja">
        <table class="encabezado">
            <tr>
                <td style="width:65%;">
                    <div class="nombre-empresa"><?= e($nombreEmpresa) ?></div>
                    <div class="datos-empresa">
                        <?= e($empresa['direccion'] ?? '') ?><br>
                        Tel: <?= e($empresa['telefono'] ?? '') ?> <?= !empty($empresa['rnc']) ? '· RNC: ' . e($empresa['rnc']) : '' ?>
                    </div>
                </td>
                <td style="width:35%;">
                    <div class="titulo-doc">Receta Optica</div>
                    <div class="folio">No. <?= str_pad((string) $receta['id'], 6, '0', STR_PAD_LEFT) ?><br><?= fecha_larga($receta['fecha']) ?></div>
                </td>
            </tr>
        </table>

        <table class="datos-paciente">
            <tr>
                <td style="width:50%;"><span class="etiqueta">Paciente:</span> <strong><?= e($receta['cliente_nombres'] . ' ' . $receta['cliente_apellidos']) ?></strong></td>
                <td style="width:25%;"><span class="etiqueta">Edad:</span> <?= $receta['cliente_edad'] !== null ? (int) $receta['cliente_edad'] . ' años' : '—' ?></td>
                <td style="width:25%;"><span class="etiqueta">Cedula:</span> <?= e($receta['cedula_pasaporte'] ?? '—') ?></td>
            </tr>
        </table>

        <table class="tabla-graduacion">
            <tr><th></th><th>Esfera</th><th>Cilindro</th><th>Eje</th><th>Adicion</th><th>D.P.</th></tr>
            <tr><td>OD</td><td><?= e($receta['od_esfera'] ?? '—') ?></td><td><?= e($receta['od_cilindro'] ?? '—') ?></td><td><?= e($receta['od_eje'] ?? '—') ?></td><td><?= e($receta['od_adicion'] ?? '—') ?></td><td><?= e($receta['od_dp'] ?? '—') ?></td></tr>
            <tr><td>OI</td><td><?= e($receta['oi_esfera'] ?? '—') ?></td><td><?= e($receta['oi_cilindro'] ?? '—') ?></td><td><?= e($receta['oi_eje'] ?? '—') ?></td><td><?= e($receta['oi_adicion'] ?? '—') ?></td><td><?= e($receta['oi_dp'] ?? '—') ?></td></tr>
        </table>

        <table class="especificaciones">
            <tr>
                <td style="width:25%;"><span class="etiqueta">Tipo de lente:</span><br><?= e($receta['tipo_lente'] ?? '—') ?></td>
                <td style="width:25%;"><span class="etiqueta">Material:</span><br><?= e($receta['material'] ?? '—') ?></td>
                <td style="width:25%;"><span class="etiqueta">Color:</span><br><?= e($receta['color'] ?? '—') ?></td>
                <td style="width:25%;"><span class="etiqueta">Tratamiento:</span><br><?= e($receta['tratamiento_lente'] ?? '—') ?></td>
            </tr>
        </table>

        <?php if (!empty($receta['observaciones'])): ?>
            <p style="font-size:12px;"><span class="etiqueta">Observaciones:</span> <?= e($receta['observaciones']) ?></p>
        <?php endif; ?>

        <table class="pie">
            <tr>
                <td style="width:60%; vertical-align:bottom;">
                    <div class="firma-linea">
                        Dr(a). <?= e($receta['doctor_nombre']) ?><br>
                        <?= !empty($receta['doctor_colegiado']) ? 'Exequatur/Colegiatura: ' . e($receta['doctor_colegiado']) : 'Firma del especialista' ?>
                    </div>
                </td>
                <td style="width:40%;" class="qr-box">
                    <img src="<?= e($qrUrl) ?>" alt="Codigo QR de validacion"><br>
                    Escanee para validar esta receta<br>
                    <span class="codigo-validacion"><?= e(substr($receta['codigo_validacion'] ?? '', 0, 16)) ?>…</span>
                </td>
            </tr>
        </table>

        <?php if (($receta['estado'] ?? 'activa') === 'anulada'): ?>
            <p style="text-align:center; color:#c94a3f; font-weight:bold; margin-top:16px; border:2px solid #c94a3f; padding:6px;">RECETA ANULADA — NO VALIDA</p>
        <?php endif; ?>
    </div>
</body>
</html>
