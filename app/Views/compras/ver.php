<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Compra #' . $compra['id']; ?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 font-display mb-0"><?= e($compra['proveedor_nombre']) ?></h2>
        <div class="small text-muted-soft">Compra #<?= (int) $compra['id'] ?> · <?= fecha_larga($compra['fecha']) ?> · Registrada por <?= e($compra['creado_por']) ?></div>
    </div>
    <span class="<?= estado_badge($compra['estado_pago']) ?> fs-6"><?= e(ucfirst($compra['estado_pago'])) ?></span>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Subtotal</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($compra['subtotal']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">ITBIS</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($compra['itbis']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Total</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($compra['total']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Saldo pendiente</div><div class="kpi-value <?= (float) $compra['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>" style="font-size:1.2rem"><?= moneda($compra['saldo_pendiente']) ?></div></div></div>
</div>

<div class="card mb-3">
    <div class="p-3 pb-0"><div class="form-section-title">Productos</div></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo unitario</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($compra['lineas'] as $l): ?>
                <tr>
                    <td><?= e($l['producto_nombre']) ?> <span class="text-muted-soft small font-mono"><?= e($l['producto_codigo']) ?></span></td>
                    <td class="text-end font-mono"><?= (int) $l['cantidad'] ?></td>
                    <td class="text-end font-mono"><?= moneda($l['costo_unitario']) ?></td>
                    <td class="text-end font-mono"><?= moneda($l['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card p-3">
            <div class="form-section-title">Pagos realizados</div>
            <?php if (empty($compra['pagos'])): ?>
                <p class="text-muted-soft small mb-0">Sin pagos registrados todavia.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($compra['pagos'] as $pago): ?>
                        <li class="d-flex justify-content-between border-bottom py-2 small">
                            <span><?= fecha_larga($pago['fecha']) ?> · <?= e(ucfirst($pago['metodo_pago'])) ?></span>
                            <span class="fw-semibold"><?= moneda($pago['monto']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php if ((float) $compra['saldo_pendiente'] > 0 && Auth::puede('compras.pagar')): ?>
    <div class="col-12 col-lg-6">
        <div class="card p-3">
            <div class="form-section-title">Registrar pago</div>
            <form method="POST" action="<?= e(Url::to('compras/pagar/' . $compra['id'])) ?>" class="row g-2">
                <?= Csrf::field() ?>
                <div class="col-md-4"><input type="number" step="0.01" name="monto" class="form-control" placeholder="Monto" max="<?= e((string) $compra['saldo_pendiente']) ?>" required></div>
                <div class="col-md-4">
                    <select name="metodo_pago" class="form-select" required>
                        <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="cheque">Cheque</option><option value="tarjeta">Tarjeta</option>
                    </select>
                </div>
                <div class="col-md-4"><input type="text" name="referencia" class="form-control" placeholder="Referencia"></div>
                <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Registrar pago</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
