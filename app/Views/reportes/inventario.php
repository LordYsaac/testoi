<?php use App\Core\Url; $tituloPagina = 'Inventario valorizado'; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4"><div class="card card-kpi"><div class="kpi-label">Productos activos</div><div class="kpi-value"><?= count($filas) ?></div></div></div>
    <div class="col-6 col-md-4"><div class="card card-kpi"><div class="kpi-label">Valor en costo</div><div class="kpi-value" style="font-size:1.3rem"><?= moneda($valorTotalCosto) ?></div></div></div>
    <div class="col-12 col-md-4"><div class="card card-kpi"><div class="kpi-label">Valor en venta</div><div class="kpi-value" style="font-size:1.3rem"><?= moneda($valorTotalVenta) ?></div></div></div>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(Url::to('reportes/inventario?formato=csv')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Exportar CSV</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Producto</th><th>Categoria</th><th class="text-end">Stock</th><th class="text-end">Costo unit.</th><th class="text-end">Valor costo</th><th class="text-end">Valor venta</th></tr></thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td class="fw-semibold small"><?= e($f['nombre']) ?> <span class="text-muted-soft font-mono"><?= e($f['codigo']) ?></span></td>
                    <td class="small"><?= e($f['categoria']) ?></td>
                    <td class="text-end font-mono <?= (int) $f['stock_actual'] <= (int) $f['stock_minimo'] ? 'text-danger' : '' ?>"><?= (int) $f['stock_actual'] ?></td>
                    <td class="text-end font-mono"><?= moneda($f['costo']) ?></td>
                    <td class="text-end font-mono"><?= moneda($f['valor_costo']) ?></td>
                    <td class="text-end font-mono"><?= moneda($f['valor_venta']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
