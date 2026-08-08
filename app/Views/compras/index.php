<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Compras'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted-soft mb-0">Compras registradas a proveedores.</p>
    <?php if (Auth::puede('compras.crear')): ?>
        <a href="<?= e(Url::to('compras/crear')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Registrar compra</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Proveedor</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th><th class="text-end">Accion</th></tr></thead>
            <tbody>
            <?php if (empty($compras)): ?>
                <tr><td colspan="6" class="text-center text-muted-soft py-4">No hay compras registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($compras as $c): ?>
                <tr>
                    <td class="small"><?= fecha_larga($c['fecha']) ?></td>
                    <td class="fw-semibold"><?= e($c['proveedor_nombre']) ?></td>
                    <td class="text-end font-mono"><?= moneda($c['total']) ?></td>
                    <td class="text-end font-mono <?= (float) $c['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>"><?= moneda($c['saldo_pendiente']) ?></td>
                    <td><span class="<?= estado_badge($c['estado_pago']) ?>"><?= e(ucfirst($c['estado_pago'])) ?></span></td>
                    <td class="text-end"><a href="<?= e(Url::to('compras/ver/' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
