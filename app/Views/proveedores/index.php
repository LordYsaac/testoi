<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Proveedores'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('proveedores')) ?>" class="row g-2 align-items-end">
        <div class="col-8 col-md-10"><input type="text" name="q" class="form-control" placeholder="Buscar por nombre, contacto o RNC..." value="<?= e($busqueda) ?>"></div>
        <div class="col-4 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
            <?php if (Auth::puede('proveedores.crear')): ?>
                <a href="<?= e(Url::to('proveedores/crear')) ?>" class="btn btn-accent flex-grow-1"><i class="bi bi-plus-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Proveedor</th><th>Contacto</th><th>RNC</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php if (empty($proveedores)): ?>
                <tr><td colspan="5" class="text-center text-muted-soft py-4">No hay proveedores registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($proveedores as $p): ?>
                <tr>
                    <td class="fw-semibold"><?= e($p['nombre']) ?></td>
                    <td class="small"><?= e($p['contacto_nombre'] ?? '—') ?><br><span class="text-muted-soft"><?= e($p['telefono'] ?? '') ?></span></td>
                    <td class="font-mono small"><?= e($p['rnc'] ?? '—') ?></td>
                    <td><span class="<?= estado_badge($p['estado']) ?>"><?= e(ucfirst($p['estado'])) ?></span></td>
                    <td class="text-end">
                        <a href="<?= e(Url::to('proveedores/ver/' . $p['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::puede('proveedores.editar')): ?>
                        <a href="<?= e(Url::to('proveedores/editar/' . $p['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
