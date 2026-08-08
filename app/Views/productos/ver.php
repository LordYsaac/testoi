<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; $tituloPagina = $producto['nombre']; ?>

<div class="card p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle" style="width:56px;height:56px;border-radius:.65rem;"><i class="bi bi-box-seam fs-4"></i></div>
            <div>
                <h2 class="h5 font-display mb-0"><?= e($producto['nombre']) ?></h2>
                <div class="small text-muted-soft font-mono"><?= e($producto['codigo']) ?> · <?= e($producto['categoria_nombre']) ?> <?= $producto['marca'] ? '· ' . e($producto['marca']) : '' ?></div>
            </div>
        </div>
        <?php if (Auth::puede('productos.editar')): ?>
            <a href="<?= e(Url::to('productos/editar/' . $producto['id'])) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card card-kpi"><div class="kpi-label">Stock actual</div><div class="kpi-value <?= (int) $producto['stock_actual'] <= (int) $producto['stock_minimo'] ? 'text-danger' : '' ?>"><?= (int) $producto['stock_actual'] ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi"><div class="kpi-label">Stock minimo</div><div class="kpi-value"><?= (int) $producto['stock_minimo'] ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi"><div class="kpi-label">Costo</div><div class="kpi-value"><?= moneda($producto['costo']) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi"><div class="kpi-label">Precio</div><div class="kpi-value"><?= moneda($producto['precio']) ?></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card p-3 mb-3">
            <div class="form-section-title">Informacion</div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted-soft small">Modelo</td><td><?= e($producto['modelo'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Color</td><td><?= e($producto['color'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Material</td><td><?= e($producto['material'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Proveedor</td><td><?= e($producto['proveedor_nombre'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Ubicacion</td><td><?= e($producto['ubicacion'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Lote</td><td><?= e($producto['lote'] ?? '—') ?></td></tr>
                <tr><td class="text-muted-soft small">Vencimiento</td><td><?= $producto['fecha_vencimiento'] ? fecha_larga($producto['fecha_vencimiento']) : '—' ?></td></tr>
                <tr><td class="text-muted-soft small">Codigo de barras</td><td class="font-mono"><?= e($producto['codigo_barras'] ?? '—') ?></td></tr>
            </table>
        </div>

        <?php if (Auth::puede('inventario.ajustar')): ?>
        <div class="card p-3">
            <div class="form-section-title">Ajuste rapido de inventario</div>
            <form method="POST" action="<?= e(Url::to('productos/ajustar/' . $producto['id'])) ?>">
                <?= Csrf::field() ?>
                <div class="mb-2">
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                        <option value="ajuste">Ajuste (con signo +/-)</option>
                    </select>
                </div>
                <div class="mb-2">
                    <input type="number" name="cantidad" class="form-control form-control-sm" placeholder="Cantidad" required>
                </div>
                <div class="mb-2">
                    <input type="text" name="motivo" class="form-control form-control-sm" placeholder="Motivo (opcional)">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Registrar movimiento</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="p-3 pb-0"><div class="form-section-title">Kardex (movimientos de inventario)</div></div>
            <?php if (empty($kardex)): ?>
                <p class="text-muted-soft text-center py-4 mb-0">Sin movimientos registrados.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th><th class="text-end">Saldo</th><th>Motivo</th><th>Usuario</th></tr></thead>
                        <tbody>
                        <?php foreach ($kardex as $k): ?>
                            <tr>
                                <td class="small"><?= fecha_hora($k['fecha']) ?></td>
                                <td><span class="badge badge-neutral"><?= e(ucfirst($k['tipo'])) ?></span></td>
                                <td class="text-end font-mono <?= (int) $k['cantidad'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $k['cantidad'] > 0 ? '+' : '' ?><?= (int) $k['cantidad'] ?></td>
                                <td class="text-end font-mono fw-semibold"><?= (int) $k['saldo_acumulado'] ?></td>
                                <td class="small text-muted-soft"><?= e($k['motivo'] ?? '—') ?></td>
                                <td class="small"><?= e($k['usuario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
