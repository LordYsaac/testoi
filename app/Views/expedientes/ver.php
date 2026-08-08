<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
$tituloPagina = 'Expediente clinico';

function campo_od_oi(string $etiqueta, ?string $od, ?string $oi): string
{
    if (($od === null || $od === '') && ($oi === null || $oi === '')) {
        return '';
    }
    return '<tr><td class="text-muted-soft small py-1">' . e($etiqueta) . '</td><td class="py-1">' . e($od ?? '—') . '</td><td class="py-1">' . e($oi ?? '—') . '</td></tr>';
}
?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 font-display mb-0"><?= e($expediente['cliente_nombres'] . ' ' . $expediente['cliente_apellidos']) ?></h2>
        <div class="small text-muted-soft"><?= e($expediente['codigo_cliente']) ?> · <?= fecha_hora($expediente['fecha']) ?> · Dr(a). <?= e($expediente['doctor_nombre'] ?? 'N/D') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(Url::to('clientes/ver/' . $expediente['cliente_id'])) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver al paciente</a>
        <?php if (Auth::puede('expedientes.editar')): ?>
            <a href="<?= e(Url::to('expedientes/editar/' . $expediente['id'])) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="card p-3 mb-3">
    <div class="form-section-title">Consulta</div>
    <p class="mb-1"><strong>Motivo:</strong> <?= e($expediente['motivo_consulta']) ?></p>
    <?php if ($expediente['proxima_cita']): ?><p class="mb-0 small text-muted-soft">Proxima cita sugerida: <?= fecha_larga($expediente['proxima_cita']) ?></p><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Agudeza visual</div>
            <table class="table table-sm mb-0">
                <thead><tr><th></th><th>OD</th><th>OI</th></tr></thead>
                <tbody>
                    <?= campo_od_oi('Sin correccion', $expediente['od_sin_correccion'], $expediente['oi_sin_correccion']) ?>
                    <?= campo_od_oi('Con correccion', $expediente['od_con_correccion'], $expediente['oi_con_correccion']) ?>
                    <?= campo_od_oi('Vision cercana', $expediente['od_vision_cercana'], $expediente['oi_vision_cercana']) ?>
                    <?= campo_od_oi('Vision lejana', $expediente['od_vision_lejana'], $expediente['oi_vision_lejana']) ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Refraccion</div>
            <table class="table table-sm mb-0">
                <thead><tr><th></th><th>OD</th><th>OI</th></tr></thead>
                <tbody>
                    <?= campo_od_oi('Esfera', $expediente['od_esfera'], $expediente['oi_esfera']) ?>
                    <?= campo_od_oi('Cilindro', $expediente['od_cilindro'], $expediente['oi_cilindro']) ?>
                    <?= campo_od_oi('Eje', $expediente['r_od_eje'], $expediente['r_oi_eje']) ?>
                    <?= campo_od_oi('Adicion', $expediente['od_adicion'], $expediente['oi_adicion']) ?>
                </tbody>
            </table>
            <?php if ($expediente['dp_binocular'] || $expediente['dp_od']): ?>
                <p class="small text-muted-soft mt-2 mb-0">DP: <?= e($expediente['dp_binocular'] ?? '—') ?> mm (binocular) · OD <?= e($expediente['dp_od'] ?? '—') ?> / OI <?= e($expediente['dp_oi'] ?? '—') ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Queratometria y tonometria</div>
            <table class="table table-sm mb-0">
                <thead><tr><th></th><th>OD</th><th>OI</th></tr></thead>
                <tbody>
                    <?= campo_od_oi('K1', $expediente['od_k1'], $expediente['oi_k1']) ?>
                    <?= campo_od_oi('K2', $expediente['od_k2'], $expediente['oi_k2']) ?>
                    <?= campo_od_oi('Eje K', $expediente['k_od_eje'], $expediente['k_oi_eje']) ?>
                    <?= campo_od_oi('Tonometria (mmHg)', $expediente['tono_od'], $expediente['tono_oi']) ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Biomicroscopia / Fondo de ojo</div>
            <table class="table table-sm mb-0">
                <thead><tr><th></th><th>OD</th><th>OI</th></tr></thead>
                <tbody>
                    <?= campo_od_oi('Cornea', $expediente['od_cornea'], $expediente['oi_cornea']) ?>
                    <?= campo_od_oi('Cristalino', $expediente['od_cristalino'], $expediente['oi_cristalino']) ?>
                    <?= campo_od_oi('Papila', $expediente['od_papila'], $expediente['oi_papila']) ?>
                    <?= campo_od_oi('Macula', $expediente['od_macula'], $expediente['oi_macula']) ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Diagnostico</div>
            <?php if (empty($expediente['diagnosticos'])): ?>
                <p class="text-muted-soft small mb-0">Sin diagnostico registrado.</p>
            <?php else: ?>
                <ul class="mb-0">
                    <?php foreach ($expediente['diagnosticos'] as $d): ?><li><?= e($d['diagnostico']) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="form-section-title">Tratamiento</div>
            <?php if (empty($expediente['tratamientos'])): ?>
                <p class="text-muted-soft small mb-0">Sin tratamiento registrado.</p>
            <?php else: ?>
                <ul class="mb-0">
                    <?php foreach ($expediente['tratamientos'] as $t): ?><li><?= e($t['tratamiento']) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($expediente['indicaciones']): ?><p class="small mt-2 mb-0"><strong>Indicaciones:</strong> <?= e($expediente['indicaciones']) ?></p><?php endif; ?>
        </div>
    </div>
</div>

<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-section-title mb-0 border-0 pb-0">Adjuntos (PDF, imagenes, estudios)</div>
        <?php if (Auth::puede('expedientes.editar')): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#formAdjunto"><i class="bi bi-paperclip me-1"></i>Adjuntar</button>
        <?php endif; ?>
    </div>

    <div class="collapse mb-3" id="formAdjunto">
        <form method="POST" action="<?= e(Url::to('expedientes/adjuntar/' . $expediente['id'])) ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
            <?= Csrf::field() ?>
            <div class="col-md-6"><input type="file" name="adjunto" class="form-control form-control-sm" required></div>
            <div class="col-md-4"><input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripcion (opcional)"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Subir</button></div>
        </form>
    </div>

    <?php if (empty($expediente['adjuntos'])): ?>
        <p class="text-muted-soft small mb-0">Sin archivos adjuntos.</p>
    <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($expediente['adjuntos'] as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><i class="bi bi-file-earmark me-2 text-primary-brand"></i><?= e($a['nombre_original']) ?> <span class="text-muted-soft small">(<?= e($a['descripcion'] ?? $a['tipo']) ?>)</span></span>
                    <?php if (Auth::puede('expedientes.editar')): ?>
                    <a href="<?= e(Url::to('expedientes/adjunto/eliminar/' . $a['id'])) ?>" class="text-danger small" onclick="return confirm('¿Eliminar este adjunto?')"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
