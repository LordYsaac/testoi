<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = $proveedor['nombre']; ?>

<div class="card p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h5 font-display mb-0"><?= e($proveedor['nombre']) ?></h2>
            <div class="small text-muted-soft"><?= e($proveedor['contacto_nombre'] ?? 'Sin contacto registrado') ?> <?= $proveedor['telefono'] ? '· ' . e($proveedor['telefono']) : '' ?></div>
        </div>
        <?php if (Auth::puede('proveedores.editar')): ?>
            <a href="<?= e(Url::to('proveedores/editar/' . $proveedor['id'])) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="card card-kpi"><div class="kpi-label">Total comprado</div><div class="kpi-value"><?= moneda($estadoCuenta['total_comprado']) ?></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card card-kpi"><div class="kpi-label">Saldo pendiente</div><div class="kpi-value <?= (float) $estadoCuenta['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>"><?= moneda($estadoCuenta['saldo_pendiente']) ?></div></div>
    </div>
</div>

<div class="card">
    <div class="p-3 pb-0"><div class="form-section-title">Historial de compras</div></div>
    <?php if (empty($historial)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">Sin compras registradas a este proveedor.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Factura</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th><th class="text-end">Accion</th></tr></thead>
                <tbody>
                <?php foreach ($historial as $c): ?>
                    <tr>
                        <td class="small"><?= fecha_larga($c['fecha']) ?></td>
                        <td class="font-mono small"><?= e($c['numero_factura_proveedor'] ?? '—') ?></td>
                        <td class="text-end font-mono"><?= moneda($c['total']) ?></td>
                        <td class="text-end font-mono <?= (float) $c['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>"><?= moneda($c['saldo_pendiente']) ?></td>
                        <td><span class="<?= estado_badge($c['estado_pago']) ?>"><?= e(ucfirst($c['estado_pago'])) ?></span></td>
                        <td class="text-end"><a href="<?= e(Url::to('compras/ver/' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
