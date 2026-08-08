<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Emitir receta'; ?>

<div class="card p-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="avatar-circle"><?= e(mb_strtoupper(mb_substr($cliente['nombres'], 0, 1))) ?></span>
        <div>
            <div class="fw-semibold"><?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></div>
            <div class="small text-muted-soft font-mono"><?= e($cliente['codigo_cliente']) ?></div>
        </div>
    </div>
</div>

<form method="POST" action="<?= e(Url::to('recetas/guardar')) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
    <input type="hidden" name="expediente_id" value="<?= e((string) ($expedienteId ?? '')) ?>">

    <div class="card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Doctor / Optometra que emite</label>
                <select name="doctor_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="form-section-title">Graduacion</div>
        <div class="eye-grid">
            <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
            <label class="form-label mb-0">Esfera</label>
            <input type="number" step="0.25" name="od_esfera" class="form-control form-control-sm">
            <input type="number" step="0.25" name="oi_esfera" class="form-control form-control-sm">
            <label class="form-label mb-0">Cilindro</label>
            <input type="number" step="0.25" name="od_cilindro" class="form-control form-control-sm">
            <input type="number" step="0.25" name="oi_cilindro" class="form-control form-control-sm">
            <label class="form-label mb-0">Eje</label>
            <input type="number" min="0" max="180" name="od_eje" class="form-control form-control-sm">
            <input type="number" min="0" max="180" name="oi_eje" class="form-control form-control-sm">
            <label class="form-label mb-0">Adicion</label>
            <input type="number" step="0.25" name="od_adicion" class="form-control form-control-sm">
            <input type="number" step="0.25" name="oi_adicion" class="form-control form-control-sm">
            <label class="form-label mb-0">DP</label>
            <input type="number" step="0.5" name="od_dp" class="form-control form-control-sm">
            <input type="number" step="0.5" name="oi_dp" class="form-control form-control-sm">
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="form-section-title">Especificaciones del lente</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tipo de lente</label>
                <select name="tipo_lente" class="form-select">
                    <option value="">—</option>
                    <option>Monofocal</option><option>Bifocal</option><option>Progresivo</option><option>Lente de contacto blando</option><option>Lente de contacto rigido</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Material</label>
                <select name="material" class="form-select">
                    <option value="">—</option>
                    <option>CR-39</option><option>Policarbonato</option><option>Alto indice 1.61</option><option>Alto indice 1.67</option><option>Trivex</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Color</label>
                <input type="text" name="color" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tratamiento</label>
                <input type="text" name="tratamiento_lente" class="form-control" placeholder="Antirreflejo, Filtro azul, Fotocromatico...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= e(Url::to('clientes/ver/' . $cliente['id'])) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-prescription2 me-1"></i>Emitir receta</button>
    </div>
</form>
