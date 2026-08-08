<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Facturacion'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('facturas')) ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-7"><input type="text" name="q" class="form-control" placeholder="Cliente o NCF..." value="<?= e($busqueda) ?>"></div>
        <div class="col-6 col-md-3">
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                <option value="anulada" <?= $estado === 'anulada' ? 'selected' : '' ?>>Anulada</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
            <?php if (Auth::puede('facturas.crear')): ?>
                <a href="<?= e(Url::to('facturas/crear')) ?>" class="btn btn-accent flex-grow-1"><i class="bi bi-plus-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Fecha</th><th>NCF</th><th>Cliente</th><th>Tipo</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th><th class="text-end">Accion</th></tr></thead>
            <tbody>
            <?php if (empty($facturas)): ?>
                <tr><td colspan="8" class="text-center text-muted-soft py-4">No hay facturas registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($facturas as $f): ?>
                <tr>
                    <td class="small"><?= fecha_hora($f['fecha']) ?></td>
                    <td class="font-mono small"><?= e($f['ncf'] ?? '—') ?></td>
                    <td class="fw-semibold"><?= e($f['cliente']) ?></td>
                    <td class="small"><?= e(ucfirst(str_replace('_', ' ', $f['tipo']))) ?></td>
                    <td class="text-end font-mono"><?= moneda($f['total']) ?></td>
                    <td class="text-end font-mono <?= (float) $f['saldo_pendiente'] > 0 ? 'text-danger' : '' ?>"><?= moneda($f['saldo_pendiente']) ?></td>
                    <td><span class="<?= estado_badge($f['estado']) ?>"><?= e(ucfirst($f['estado'])) ?></span></td>
                    <td class="text-end"><a href="<?= e(Url::to('facturas/ver/' . $f['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
