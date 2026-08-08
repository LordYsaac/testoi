<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Clientes'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('clientes')) ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label">Buscar</label>
            <input type="text" name="q" class="form-control" placeholder="Nombre, codigo, cedula o telefono..." value="<?= e($busqueda) ?>">
        </div>
        <div class="col-8 col-md-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="col-4 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
            <?php if (Auth::puede('clientes.crear')): ?>
                <a href="<?= e(Url::to('clientes/crear')) ?>" class="btn btn-accent flex-grow-1"><i class="bi bi-plus-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Cedula/Pasaporte</th><th>Seguro</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php if (empty($clientes)): ?>
                <tr><td colspan="6" class="text-center text-muted-soft py-4">No se encontraron clientes.</td></tr>
            <?php endif; ?>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle"><?= e(mb_strtoupper(mb_substr($c['nombres'], 0, 1))) ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></div>
                                <div class="small text-muted-soft font-mono"><?= e($c['codigo_cliente']) ?> <?= $c['edad'] !== null ? '· ' . (int) $c['edad'] . ' años' : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= e($c['telefono'] ?? '—') ?><br><span class="text-muted-soft"><?= e($c['email'] ?? '') ?></span></td>
                    <td class="font-mono small"><?= e($c['cedula_pasaporte'] ?? '—') ?></td>
                    <td class="small"><?= e($c['seguro_medico'] ?? 'Particular') ?></td>
                    <td><span class="<?= estado_badge($c['estado']) ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
                    <td class="text-end">
                        <a href="<?= e(Url::to('clientes/ver/' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::puede('clientes.editar')): ?>
                        <a href="<?= e(Url::to('clientes/editar/' . $c['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="<?= e(Url::to('clientes?pagina=' . $p . '&q=' . urlencode($busqueda) . '&estado=' . urlencode($estado))) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
