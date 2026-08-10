<?php use App\Core\Url; $tituloPagina = 'Reporte de ventas'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('reportes/ventas')) ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
        <div class="col-6 col-md-3"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
        <div class="col-6 col-md-3"><a href="<?= e(Url::to('reportes/ventas?desde=' . $desde . '&hasta=' . $hasta . '&formato=csv')) ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-download me-1"></i>CSV</a></div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Facturas</div><div class="kpi-value" style="font-size:1.3rem"><?= (int) $totales['facturas'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Subtotal</div><div class="kpi-value" style="font-size:1.3rem"><?= moneda($totales['subtotal']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">ITBIS</div><div class="kpi-value" style="font-size:1.3rem"><?= moneda($totales['itbis']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Total</div><div class="kpi-value" style="font-size:1.3rem"><?= moneda($totales['total']) ?></div></div></div>
</div>

<div class="card">
    <?php if (empty($filas)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">No hay ventas en el periodo seleccionado.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Dia</th><th class="text-end">Facturas</th><th class="text-end">Subtotal</th><th class="text-end">Descuento</th><th class="text-end">ITBIS</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><?= fecha_larga($f['dia']) ?></td>
                        <td class="text-end font-mono"><?= (int) $f['facturas'] ?></td>
                        <td class="text-end font-mono"><?= moneda($f['subtotal']) ?></td>
                        <td class="text-end font-mono"><?= moneda($f['descuento']) ?></td>
                        <td class="text-end font-mono"><?= moneda($f['itbis']) ?></td>
                        <td class="text-end font-mono fw-semibold"><?= moneda($f['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
