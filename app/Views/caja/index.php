<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Caja'; ?>

<?php if (!$sesionAbierta): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <div class="card p-4 text-center">
                <i class="bi bi-cash-stack text-primary-brand" style="font-size:2.5rem;"></i>
                <h2 class="h5 font-display mt-2">No tiene una caja abierta</h2>
                <p class="text-muted-soft small">Abra su caja para comenzar a registrar ventas y movimientos de efectivo.</p>
                <form method="POST" action="<?= e(Url::to('caja/abrir')) ?>" class="text-start mt-2">
                    <?= Csrf::field() ?>
                    <label class="form-label">Monto de apertura</label>
                    <input type="number" step="0.01" name="monto_apertura" class="form-control mb-2" value="0" required>
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control mb-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-unlock me-1"></i>Abrir caja</button>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Apertura</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($sesionAbierta['monto_apertura']) ?></div></div></div>
        <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Ingresos</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($resumen['ingresos']) ?></div></div></div>
        <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Egresos</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda($resumen['egresos']) ?></div></div></div>
        <div class="col-6 col-md-3"><div class="card card-kpi"><div class="kpi-label">Esperado en caja</div><div class="kpi-value" style="font-size:1.2rem"><?= moneda((float) $sesionAbierta['monto_apertura'] + (float) $resumen['ingresos'] - (float) $resumen['egresos']) ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                    <div class="form-section-title mb-0 border-0 pb-0">Movimientos de esta sesion</div>
                    <span class="badge badge-neutral">Abierta desde <?= fecha_hora($sesionAbierta['fecha_apertura']) ?></span>
                </div>
                <?php if (empty($movimientos)): ?>
                    <p class="text-muted-soft text-center py-4 mb-0">Sin movimientos todavia.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Hora</th><th>Tipo</th><th>Concepto</th><th class="text-end">Monto</th></tr></thead>
                            <tbody>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td class="small font-mono"><?= date('h:i A', strtotime($m['fecha'])) ?></td>
                                    <td><span class="badge <?= $m['tipo'] === 'egreso' ? 'badge-danger' : 'badge-success' ?>"><?= e(ucfirst($m['tipo'])) ?></span></td>
                                    <td class="small"><?= e($m['concepto']) ?></td>
                                    <td class="text-end font-mono"><?= moneda($m['monto']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card p-3 mb-3">
                <div class="form-section-title">Registrar ingreso/egreso manual</div>
                <form method="POST" action="<?= e(Url::to('caja/movimiento')) ?>" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-5">
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="ingreso">Ingreso</option>
                            <option value="egreso">Egreso</option>
                        </select>
                    </div>
                    <div class="col-7"><input type="number" step="0.01" name="monto" class="form-control form-control-sm" placeholder="Monto" required></div>
                    <div class="col-12"><input type="text" name="concepto" class="form-control form-control-sm" placeholder="Concepto"></div>
                    <div class="col-12"><button class="btn btn-outline-primary btn-sm w-100">Registrar</button></div>
                </form>
            </div>

            <div class="card p-3 border-danger">
                <div class="form-section-title">Cerrar caja</div>
                <form method="POST" action="<?= e(Url::to('caja/cerrar')) ?>" data-confirmar="¿Cerrar la caja? Esta accion no se puede deshacer.">
                    <?= Csrf::field() ?>
                    <label class="form-label small">Monto contado en caja</label>
                    <input type="number" step="0.01" name="monto_declarado" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-danger btn-sm w-100"><i class="bi bi-lock me-1"></i>Cerrar caja</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($historial)): ?>
<div class="card mt-3">
    <div class="p-3 pb-0"><div class="form-section-title">Historial de sesiones</div></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Usuario</th><th>Apertura</th><th>Cierre</th><th class="text-end">Diferencia</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td class="small"><?= e($h['usuario']) ?></td>
                    <td class="small"><?= fecha_hora($h['fecha_apertura']) ?></td>
                    <td class="small"><?= $h['fecha_cierre'] ? fecha_hora($h['fecha_cierre']) : '—' ?></td>
                    <td class="text-end font-mono <?= (float) ($h['diferencia'] ?? 0) < 0 ? 'text-danger' : '' ?>"><?= $h['diferencia'] !== null ? moneda($h['diferencia']) : '—' ?></td>
                    <td><span class="<?= estado_badge($h['estado']) ?>"><?= e(ucfirst($h['estado'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
