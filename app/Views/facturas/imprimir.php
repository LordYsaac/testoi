<?php $nombreEmpresa = $empresa['nombre_comercial'] ?? $empresa['nombre_empresa'] ?? 'Optica'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura #<?= (int) $factura['id'] ?></title>
<style>
    body { font-family: "DejaVu Sans", Helvetica, Arial, sans-serif; color: #16232b; font-size: 13px; }
    .print-toolbar { text-align: right; padding: 10px 0; }
    .hoja { max-width: 720px; margin: 0 auto; padding: 28px; border: 1px solid #d8e2e2; }
    .encabezado { width: 100%; border-bottom: 3px solid #0e6e6e; padding-bottom: 12px; margin-bottom: 16px; }
    .nombre-empresa { font-size: 19px; font-weight: bold; color: #0a4f4f; }
    .datos-empresa { font-size: 11px; color: #52696f; line-height: 1.5; }
    .titulo-doc { text-align: right; font-size: 15px; font-weight: bold; color: #0e6e6e; text-transform: uppercase; }
    .folio { text-align: right; font-size: 11px; color: #52696f; }
    .datos-cliente { width: 100%; margin-bottom: 14px; font-size: 12px; }
    .etiqueta { color: #52696f; }
    table.detalle { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
    table.detalle th, table.detalle td { border: 1px solid #d8e2e2; padding: 6px 8px; font-size: 12px; }
    table.detalle th { background: #e6f2f2; color: #0a4f4f; text-align: left; }
    table.detalle td.num, table.detalle th.num { text-align: right; }
    .totales { width: 260px; margin-left: auto; font-size: 12.5px; }
    .totales td { padding: 3px 0; }
    .totales .total-final td { font-weight: bold; border-top: 1px solid #16232b; padding-top: 6px; }
    .anulada { text-align: center; color: #c94a3f; font-weight: bold; margin-top: 16px; border: 2px solid #c94a3f; padding: 6px; }
    @media print { .print-toolbar { display: none; } .hoja { border: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="print-toolbar no-print">
        <button onclick="window.print()" style="padding:8px 16px;background:#0e6e6e;color:#fff;border:none;border-radius:6px;cursor:pointer;">Imprimir / Guardar como PDF</button>
    </div>

    <div class="hoja">
        <table class="encabezado"><tr>
            <td style="width:65%;">
                <div class="nombre-empresa"><?= e($nombreEmpresa) ?></div>
                <div class="datos-empresa"><?= e($empresa['direccion'] ?? '') ?><br>Tel: <?= e($empresa['telefono'] ?? '') ?> <?= !empty($empresa['rnc']) ? '· RNC: ' . e($empresa['rnc']) : '' ?></div>
            </td>
            <td style="width:35%;">
                <div class="titulo-doc"><?= $factura['tipo'] === 'cotizacion' ? 'Cotizacion' : 'Factura' ?></div>
                <div class="folio">
                    No. <?= str_pad((string) $factura['id'], 6, '0', STR_PAD_LEFT) ?><br>
                    <?php if ($factura['ncf']): ?>NCF: <?= e($factura['ncf']) ?><br><?php endif; ?>
                    <?= fecha_larga($factura['fecha']) ?>
                </div>
            </td>
        </tr></table>

        <table class="datos-cliente"><tr>
            <td style="width:60%;"><span class="etiqueta">Cliente:</span> <strong><?= e($factura['cliente_nombres'] . ' ' . $factura['cliente_apellidos']) ?></strong></td>
            <td style="width:40%;"><span class="etiqueta">Cedula:</span> <?= e($factura['cedula_pasaporte'] ?? '—') ?></td>
        </tr></table>

        <table class="detalle">
            <tr><th>Descripcion</th><th class="num">Cant.</th><th class="num">Precio</th><th class="num">Desc.</th><th class="num">Subtotal</th></tr>
            <?php foreach ($factura['lineas'] as $l): ?>
                <tr>
                    <td><?= e($l['descripcion']) ?></td>
                    <td class="num"><?= (float) $l['cantidad'] ?></td>
                    <td class="num"><?= moneda($l['precio_unitario']) ?></td>
                    <td class="num"><?= moneda($l['descuento']) ?></td>
                    <td class="num"><?= moneda($l['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <table class="totales">
            <tr><td>Subtotal</td><td class="num" style="text-align:right;"><?= moneda($factura['subtotal']) ?></td></tr>
            <tr><td>Descuento</td><td class="num" style="text-align:right;"><?= moneda($factura['descuento']) ?></td></tr>
            <tr><td>ITBIS</td><td class="num" style="text-align:right;"><?= moneda($factura['itbis']) ?></td></tr>
            <tr class="total-final"><td>Total</td><td class="num" style="text-align:right;"><?= moneda($factura['total']) ?></td></tr>
            <?php if ((float) ($factura['vuelto'] ?? 0) > 0): ?>
            <tr><td>Vuelto</td><td class="num" style="text-align:right;font-weight:bold;"><?= moneda($factura['vuelto']) ?></td></tr>
            <?php endif; ?>
            <?php if ((float) $factura['saldo_pendiente'] > 0): ?>
            <tr><td>Saldo pendiente</td><td class="num" style="text-align:right;color:#c94a3f;"><?= moneda($factura['saldo_pendiente']) ?></td></tr>
            <?php endif; ?>
        </table>

        <?php if (!empty($empresa['pie_factura'])): ?>
            <p style="font-size:11px; color:#52696f; margin-top:20px;"><?= e($empresa['pie_factura']) ?></p>
        <?php endif; ?>

        <?php if ($factura['estado'] === 'anulada'): ?>
            <p class="anulada">FACTURA ANULADA</p>
        <?php endif; ?>
    </div>
</body>
</html>
