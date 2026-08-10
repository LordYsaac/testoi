<?php use App\Core\Url; $tituloPagina = 'Clientes frecuentes'; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(Url::to('reportes/clientes-frecuentes?formato=csv')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Exportar CSV</a>
</div>

<div class="card">
    <?php if (empty($filas)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">Aun no hay suficiente historial de compras.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>#</th><th>Cliente</th><th class="text-end">Facturas</th><th class="text-end">Monto total</th><th>Ultima compra</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $i => $f): ?>
                    <tr>
                        <td class="text-muted-soft"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= e($f['cliente']) ?> <span class="text-muted-soft small font-mono"><?= e($f['codigo_cliente']) ?></span></td>
                        <td class="text-end font-mono"><?= (int) $f['total_facturas'] ?></td>
                        <td class="text-end font-mono fw-semibold"><?= moneda($f['monto_total']) ?></td>
                        <td class="small"><?= fecha_larga($f['ultima_compra']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
