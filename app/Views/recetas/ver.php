<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Receta'; ?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 font-display mb-0"><?= e($receta['cliente_nombres'] . ' ' . $receta['cliente_apellidos']) ?></h2>
        <div class="small text-muted-soft"><?= fecha_hora($receta['fecha']) ?> · Dr(a). <?= e($receta['doctor_nombre']) ?> · <span class="<?= estado_badge($receta['estado']) ?>"><?= e(ucfirst($receta['estado'])) ?></span></div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(Url::to('recetas/imprimir/' . $receta['id'])) ?>" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Imprimir</a>
        <?php if ($receta['estado'] === 'activa' && Auth::puede('recetas.anular')): ?>
            <form method="POST" action="<?= e(Url::to('recetas/anular/' . $receta['id'])) ?>" data-confirmar="¿Anular esta receta?">
                <?= Csrf::field() ?>
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Anular</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card p-3">
    <div class="eye-grid mb-3">
        <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
        <div class="text-muted-soft small">Esfera</div><div><?= e($receta['od_esfera'] ?? '—') ?></div><div><?= e($receta['oi_esfera'] ?? '—') ?></div>
        <div class="text-muted-soft small">Cilindro</div><div><?= e($receta['od_cilindro'] ?? '—') ?></div><div><?= e($receta['oi_cilindro'] ?? '—') ?></div>
        <div class="text-muted-soft small">Eje</div><div><?= e($receta['od_eje'] ?? '—') ?></div><div><?= e($receta['oi_eje'] ?? '—') ?></div>
        <div class="text-muted-soft small">Adicion</div><div><?= e($receta['od_adicion'] ?? '—') ?></div><div><?= e($receta['oi_adicion'] ?? '—') ?></div>
        <div class="text-muted-soft small">DP</div><div><?= e($receta['od_dp'] ?? '—') ?></div><div><?= e($receta['oi_dp'] ?? '—') ?></div>
    </div>
    <div class="row g-2 small">
        <div class="col-md-3"><span class="text-muted-soft">Tipo de lente:</span> <?= e($receta['tipo_lente'] ?? '—') ?></div>
        <div class="col-md-3"><span class="text-muted-soft">Material:</span> <?= e($receta['material'] ?? '—') ?></div>
        <div class="col-md-3"><span class="text-muted-soft">Color:</span> <?= e($receta['color'] ?? '—') ?></div>
        <div class="col-md-3"><span class="text-muted-soft">Tratamiento:</span> <?= e($receta['tratamiento_lente'] ?? '—') ?></div>
    </div>
    <?php if ($receta['observaciones']): ?><p class="small mt-2 mb-0"><span class="text-muted-soft">Observaciones:</span> <?= e($receta['observaciones']) ?></p><?php endif; ?>
</div>
