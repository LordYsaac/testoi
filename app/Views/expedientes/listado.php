<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Historial clinico'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h6 mb-0"><?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></h2>
        <span class="small text-muted-soft font-mono"><?= e($cliente['codigo_cliente']) ?></span>
    </div>
    <?php if (Auth::puede('expedientes.crear')): ?>
        <a href="<?= e(Url::to('expedientes/crear/' . $cliente['id'])) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nueva entrada</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($historial)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">Sin entradas de historial clinico todavia.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Motivo</th><th>Diagnostico(s)</th><th>Doctor</th><th class="text-end">Accion</th></tr></thead>
                <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td class="small"><?= fecha_hora($h['fecha']) ?></td>
                        <td><?= e($h['motivo_consulta']) ?></td>
                        <td class="small text-muted-soft"><?= e($h['diagnosticos'] ?? '—') ?></td>
                        <td class="small"><?= e($h['doctor_nombre'] ?? '—') ?></td>
                        <td class="text-end"><a href="<?= e(Url::to('expedientes/ver/' . $h['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
