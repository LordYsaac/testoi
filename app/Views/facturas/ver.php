<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Factura #' . $factura['id']; ?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 font-display mb-0"><?= e($factura['cliente_nombres'] . ' ' . $factura['cliente_apellidos']) ?></h2>
        <div class="small text-muted-soft">
            <?= fecha_hora($factura['fecha']) ?> · <?= e(ucfirst(str_replace('_', ' ', $factura['tipo']))) ?>
            <?= $factura['ncf'] ? '· NCF ' . e($factura['ncf']) : '' ?> · Vendido por <?= e($factura['vendedor']) ?>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-start">
        <span class="<?= estado_badge($factura['estado']) ?> fs-6"><?= e(ucfirst($factura['estado'])) ?></span>
        <a href="<?= e(Url::to('facturas/imprimir/' . $factura['id'])) ?>" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Imprimir</a>
        <?php if ($factura['estado'] !== 'anulada' && Auth::puede('facturas.anular')): ?>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#formAnular"><i class="bi bi-x-circle me-1"></i>Anular</button>
        <?php endif; ?>
    </div>
</div>

<div class="collapse mb-3" id="formAnular">
    <div class="card p-3 border-danger">
        <form method="POST" action="<?= e(Url::to('facturas/anular/' . $factura['id'])) ?>" data-confirmar="¿Anular esta factura? El inventario vendido se repondra automaticamente.">
            <?= Csrf::field() ?>
            <label class="form-label">Motivo de anulacion</label>
            <div class="input-group">
                <input type="text" name="motivo" class="form-control" required>
                <button class="btn btn-danger">Confirmar anulacion</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Subtotal</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($factura['subtotal']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">ITBIS</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($factura['itbis']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Total</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($factura['total']) ?></div></div></div>
    <div class="col-6 col-md-3">
        <?php if ((float) ($factura['vuelto'] ?? 0) > 0): ?>
            <div class="card card-kpi"><div class="kpi-label">Vuelto entregado</div><div class="kpi-value text-primary-brand" style="font-size:1.2rem"><?= moneda($factura['vuelto']) ?></div></div>
        <?php else: ?>
            <div class="card card-kpi"><div class="kpi-label">Saldo pendiente</div><div class="kpi-value <?= (float) $factura['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>" style="font-size:1.2rem"><?= moneda($factura['saldo_pendiente']) ?></div></div>
        <?php endif; ?>
    </div>
</div>
<?php if ((float) ($factura['vuelto'] ?? 0) > 0 && (float) $factura['saldo_pendiente'] > 0): ?>
<div class="alert alert-warning small py-2 mb-3">Esta factura tiene vuelto entregado (<?= moneda($factura['vuelto']) ?>) y ademas saldo pendiente (<?= moneda($factura['saldo_pendiente']) ?>) — revise los pagos registrados.</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="p-3 pb-0"><div class="form-section-title">Detalle</div></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Descripcion</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Descuento</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($factura['lineas'] as $l): ?>
                <tr>
                    <td><?= e($l['descripcion']) ?></td>
                    <td class="text-end font-mono"><?= (float) $l['cantidad'] ?></td>
                    <td class="text-end font-mono"><?= moneda($l['precio_unitario']) ?></td>
                    <td class="text-end font-mono"><?= moneda($l['descuento']) ?></td>
                    <td class="text-end font-mono"><?= moneda($l['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-3">
    <div class="form-section-title">Pagos</div>
    <?php if (empty($factura['pagos'])): ?>
        <p class="text-muted-soft small mb-0">Sin pagos registrados (factura a credito sin abonos aun).</p>
    <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($factura['pagos'] as $p): ?>
                <li class="d-flex justify-content-between border-bottom py-2 small">
                    <span><?= fecha_hora($p['fecha']) ?> · <?= e(ucfirst($p['metodo_pago'])) ?></span>
                    <span class="fw-semibold"><?= moneda($p['monto']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
