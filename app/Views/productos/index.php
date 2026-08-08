<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Inventario'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('productos')) ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label">Buscar</label>
            <input type="text" name="q" class="form-control" placeholder="Nombre, codigo, marca..." value="<?= e($busqueda) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Categoria</label>
            <select name="categoria_id" class="form-select">
                <option value="0">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoriaId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
            <?php if (Auth::puede('productos.crear')): ?>
                <a href="<?= e(Url::to('productos/crear')) ?>" class="btn btn-accent flex-grow-1"><i class="bi bi-plus-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Producto</th><th>Categoria</th><th>Costo</th><th>Precio</th><th>Stock</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php if (empty($productos)): ?>
                <tr><td colspan="7" class="text-center text-muted-soft py-4">No se encontraron productos.</td></tr>
            <?php endif; ?>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($p['nombre']) ?></div>
                        <div class="small text-muted-soft font-mono"><?= e($p['codigo']) ?> <?= $p['marca'] ? '· ' . e($p['marca']) : '' ?></div>
                    </td>
                    <td class="small"><?= e($p['categoria_nombre']) ?></td>
                    <td class="font-mono small"><?= moneda($p['costo']) ?></td>
                    <td class="font-mono small fw-semibold"><?= moneda($p['precio']) ?></td>
                    <td>
                        <span class="<?= (int) $p['stock_actual'] <= (int) $p['stock_minimo'] ? 'badge-danger' : 'badge-success' ?> px-2 py-1 rounded">
                            <?= (int) $p['stock_actual'] ?>
                        </span>
                        <span class="text-muted-soft small">/ min <?= (int) $p['stock_minimo'] ?></span>
                    </td>
                    <td><span class="<?= estado_badge($p['estado']) ?>"><?= e(ucfirst($p['estado'])) ?></span></td>
                    <td class="text-end">
                        <a href="<?= e(Url::to('productos/ver/' . $p['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::puede('productos.editar')): ?>
                        <a href="<?= e(Url::to('productos/editar/' . $p['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
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
                <a class="page-link" href="<?= e(Url::to('productos?pagina=' . $p . '&q=' . urlencode($busqueda) . '&categoria_id=' . $categoriaId . '&estado=' . urlencode($estado))) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
