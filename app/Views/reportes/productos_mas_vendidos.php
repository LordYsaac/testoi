<?php use App\Core\Url; $tituloPagina = 'Productos mas vendidos'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('reportes/productos-mas-vendidos')) ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
        <div class="col-6 col-md-3"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
        <div class="col-6 col-md-3"><a href="<?= e(Url::to('reportes/productos-mas-vendidos?desde=' . $desde . '&hasta=' . $hasta . '&formato=csv')) ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-download me-1"></i>CSV</a></div>
    </form>
</div>

<div class="card">
    <?php if (empty($filas)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">No hay ventas de productos en el periodo seleccionado.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>#</th><th>Producto</th><th>Categoria</th><th class="text-end">Unidades</th><th class="text-end">Monto vendido</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $i => $f): ?>
                    <tr>
                        <td class="text-muted-soft"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= e($f['nombre']) ?> <span class="text-muted-soft small font-mono"><?= e($f['codigo']) ?></span></td>
                        <td class="small"><?= e($f['categoria']) ?></td>
                        <td class="text-end font-mono"><?= (int) $f['unidades_vendidas'] ?></td>
                        <td class="text-end font-mono fw-semibold"><?= moneda($f['monto_vendido']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
