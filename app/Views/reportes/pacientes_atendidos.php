<?php use App\Core\Url; $tituloPagina = 'Pacientes atendidos'; ?>

<div class="card p-3 mb-3">
    <form method="GET" action="<?= e(Url::to('reportes/pacientes-atendidos')) ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
        <div class="col-6 col-md-3"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
        <div class="col-6 col-md-3"><a href="<?= e(Url::to('reportes/pacientes-atendidos?desde=' . $desde . '&hasta=' . $hasta . '&formato=csv')) ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-download me-1"></i>CSV</a></div>
    </form>
</div>

<div class="card">
    <div class="p-3 pb-0 small text-muted-soft"><?= count($filas) ?> consulta(s) en el periodo</div>
    <?php if (empty($filas)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">No hay consultas registradas en el periodo seleccionado.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Paciente</th><th>Doctor</th><th>Motivo</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td class="small"><?= fecha_hora($f['fecha']) ?></td>
                        <td class="fw-semibold"><?= e($f['paciente']) ?></td>
                        <td class="small"><?= e($f['doctor'] ?? '—') ?></td>
                        <td class="small"><?= e($f['motivo_consulta']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
