<?php
use App\Core\Csrf;
use App\Core\Url;

$esEdicion = !empty($expediente['id']);
$tituloPagina = $esEdicion ? 'Editar entrada clinica' : 'Nueva entrada clinica';
$accion = $esEdicion ? Url::to('expedientes/actualizar/' . $expediente['id']) : Url::to('expedientes/guardar');
$clienteId = $esEdicion ? $expediente['cliente_id'] : $cliente['id'];
$v = static fn (string $key, string $default = ''): string => e((string) ($expediente[$key] ?? $default));
?>

<div class="card p-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="avatar-circle"><?= e(mb_strtoupper(mb_substr($esEdicion ? $expediente['cliente_nombres'] : $cliente['nombres'], 0, 1))) ?></span>
        <div>
            <div class="fw-semibold"><?= $esEdicion ? e($expediente['cliente_nombres'] . ' ' . $expediente['cliente_apellidos']) : e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></div>
            <div class="small text-muted-soft font-mono"><?= e($esEdicion ? $expediente['codigo_cliente'] : $cliente['codigo_cliente']) ?></div>
        </div>
    </div>
</div>

<?php if (!$esEdicion && $antecedentes): ?>
<div class="alert alert-info small">
    <strong>Antecedentes registrados:</strong>
    <?= e($antecedentes['alergias'] ? 'Alergias: ' . $antecedentes['alergias'] . '. ' : '') ?>
    <?= e($antecedentes['medicamentos'] ? 'Medicamentos: ' . $antecedentes['medicamentos'] . '.' : '') ?>
    <?php if (!$antecedentes['alergias'] && !$antecedentes['medicamentos']): ?>Sin alergias ni medicamentos registrados.<?php endif; ?>
    <a href="<?= e(Url::to('clientes/ver/' . $cliente['id'])) ?>#tab-antecedentes" class="ms-1">Ver/editar completos</a>
</div>
<?php endif; ?>

<form method="POST" action="<?= e($accion) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="cliente_id" value="<?= (int) $clienteId ?>">

    <div class="card p-3 mb-3">
        <div class="form-section-title mb-3">Consulta</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Doctor / Optometra</label>
                <select name="doctor_id" class="form-select">
                    <option value="">Seleccione...</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (int) ($expediente['doctor_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Proxima cita sugerida</label>
                <input type="date" name="proxima_cita" class="form-control" value="<?= $v('proxima_cita') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Motivo de consulta</label>
                <textarea name="motivo_consulta" class="form-control" rows="2" required><?= $v('motivo_consulta') ?></textarea>
            </div>
        </div>
    </div>

    <div class="accordion mb-3" id="acordeonExamen">

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secAV">Agudeza visual</button></h2>
            <div id="secAV" class="accordion-collapse collapse show" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="eye-grid">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <label class="form-label mb-0">Sin correccion</label>
                    <input type="text" name="agudeza_visual[od_sin_correccion]" class="form-control form-control-sm" value="<?= $v('od_sin_correccion') ?>" placeholder="20/40">
                    <input type="text" name="agudeza_visual[oi_sin_correccion]" class="form-control form-control-sm" value="<?= $v('oi_sin_correccion') ?>" placeholder="20/40">
                    <label class="form-label mb-0">Con correccion</label>
                    <input type="text" name="agudeza_visual[od_con_correccion]" class="form-control form-control-sm" value="<?= $v('od_con_correccion') ?>" placeholder="20/20">
                    <input type="text" name="agudeza_visual[oi_con_correccion]" class="form-control form-control-sm" value="<?= $v('oi_con_correccion') ?>" placeholder="20/20">
                    <label class="form-label mb-0">Vision cercana</label>
                    <input type="text" name="agudeza_visual[od_vision_cercana]" class="form-control form-control-sm" value="<?= $v('od_vision_cercana') ?>">
                    <input type="text" name="agudeza_visual[oi_vision_cercana]" class="form-control form-control-sm" value="<?= $v('oi_vision_cercana') ?>">
                    <label class="form-label mb-0">Vision lejana</label>
                    <input type="text" name="agudeza_visual[od_vision_lejana]" class="form-control form-control-sm" value="<?= $v('od_vision_lejana') ?>">
                    <input type="text" name="agudeza_visual[oi_vision_lejana]" class="form-control form-control-sm" value="<?= $v('oi_vision_lejana') ?>">
                </div>
            </div></div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secRef">Refraccion</button></h2>
            <div id="secRef" class="accordion-collapse collapse" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="eye-grid">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <label class="form-label mb-0">Esfera</label>
                    <input type="number" step="0.25" name="refraccion[od_esfera]" class="form-control form-control-sm" value="<?= $v('od_esfera') ?>">
                    <input type="number" step="0.25" name="refraccion[oi_esfera]" class="form-control form-control-sm" value="<?= $v('oi_esfera') ?>">
                    <label class="form-label mb-0">Cilindro</label>
                    <input type="number" step="0.25" name="refraccion[od_cilindro]" class="form-control form-control-sm" value="<?= $v('od_cilindro') ?>">
                    <input type="number" step="0.25" name="refraccion[oi_cilindro]" class="form-control form-control-sm" value="<?= $v('oi_cilindro') ?>">
                    <label class="form-label mb-0">Eje</label>
                    <input type="number" min="0" max="180" name="refraccion[od_eje]" class="form-control form-control-sm" value="<?= $v('r_od_eje') ?>">
                    <input type="number" min="0" max="180" name="refraccion[oi_eje]" class="form-control form-control-sm" value="<?= $v('r_oi_eje') ?>">
                    <label class="form-label mb-0">Adicion</label>
                    <input type="number" step="0.25" name="refraccion[od_adicion]" class="form-control form-control-sm" value="<?= $v('od_adicion') ?>">
                    <input type="number" step="0.25" name="refraccion[oi_adicion]" class="form-control form-control-sm" value="<?= $v('oi_adicion') ?>">
                    <label class="form-label mb-0">Prisma</label>
                    <input type="text" name="refraccion[od_prisma]" class="form-control form-control-sm" value="<?= $v('od_prisma') ?>">
                    <input type="text" name="refraccion[oi_prisma]" class="form-control form-control-sm" value="<?= $v('oi_prisma') ?>">
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-4"><label class="form-label mb-0 small">DP binocular</label><input type="number" step="0.5" name="distancia_pupilar[dp_binocular]" class="form-control form-control-sm" value="<?= $v('dp_binocular') ?>"></div>
                    <div class="col-4"><label class="form-label mb-0 small">DP monocular OD</label><input type="number" step="0.5" name="distancia_pupilar[dp_od]" class="form-control form-control-sm" value="<?= $v('dp_od') ?>"></div>
                    <div class="col-4"><label class="form-label mb-0 small">DP monocular OI</label><input type="number" step="0.5" name="distancia_pupilar[dp_oi]" class="form-control form-control-sm" value="<?= $v('dp_oi') ?>"></div>
                </div>
            </div></div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secKT">Queratometria y tonometria</button></h2>
            <div id="secKT" class="accordion-collapse collapse" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="eye-grid mb-3">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <label class="form-label mb-0">K1</label>
                    <input type="number" step="0.01" name="queratometria[od_k1]" class="form-control form-control-sm" value="<?= $v('od_k1') ?>">
                    <input type="number" step="0.01" name="queratometria[oi_k1]" class="form-control form-control-sm" value="<?= $v('oi_k1') ?>">
                    <label class="form-label mb-0">K2</label>
                    <input type="number" step="0.01" name="queratometria[od_k2]" class="form-control form-control-sm" value="<?= $v('od_k2') ?>">
                    <input type="number" step="0.01" name="queratometria[oi_k2]" class="form-control form-control-sm" value="<?= $v('oi_k2') ?>">
                    <label class="form-label mb-0">Eje</label>
                    <input type="number" min="0" max="180" name="queratometria[od_eje]" class="form-control form-control-sm" value="<?= $v('k_od_eje') ?>">
                    <input type="number" min="0" max="180" name="queratometria[oi_eje]" class="form-control form-control-sm" value="<?= $v('k_oi_eje') ?>">
                </div>
                <div class="eye-grid">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <label class="form-label mb-0">Tonometria (mmHg)</label>
                    <input type="number" step="0.1" name="tonometria[od_valor]" class="form-control form-control-sm" value="<?= $v('tono_od') ?>">
                    <input type="number" step="0.1" name="tonometria[oi_valor]" class="form-control form-control-sm" value="<?= $v('tono_oi') ?>">
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-6"><label class="form-label mb-0 small">Metodo</label><input type="text" name="tonometria[metodo]" class="form-control form-control-sm" value="<?= $v('tono_metodo') ?>" placeholder="Aplanacion Goldmann, No contacto..."></div>
                    <div class="col-6"><label class="form-label mb-0 small">Hora</label><input type="time" name="tonometria[hora]" class="form-control form-control-sm" value="<?= $v('tono_hora') ?>"></div>
                </div>
            </div></div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secBio">Biomicroscopia (lampara de hendidura)</button></h2>
            <div id="secBio" class="accordion-collapse collapse" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="eye-grid">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <?php foreach (['parpados' => 'Parpados', 'conjuntiva' => 'Conjuntiva', 'cornea' => 'Cornea', 'camara_anterior' => 'Camara anterior', 'iris' => 'Iris', 'cristalino' => 'Cristalino'] as $campo => $etiqueta): ?>
                        <label class="form-label mb-0"><?= $etiqueta ?></label>
                        <input type="text" name="biomicroscopia[od_<?= $campo ?>]" class="form-control form-control-sm" value="<?= $v('od_' . $campo) ?>">
                        <input type="text" name="biomicroscopia[oi_<?= $campo ?>]" class="form-control form-control-sm" value="<?= $v('oi_' . $campo) ?>">
                    <?php endforeach; ?>
                </div>
                <div class="mt-2"><label class="form-label mb-0 small">Observaciones</label><textarea name="biomicroscopia[observaciones]" class="form-control form-control-sm" rows="2"><?= $v('biomicroscopia_obs') ?></textarea></div>
            </div></div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secFondo">Fondo de ojo</button></h2>
            <div id="secFondo" class="accordion-collapse collapse" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="eye-grid">
                    <div></div><div class="eye-head">OD</div><div class="eye-head">OI</div>
                    <?php foreach (['papila' => 'Papila', 'retina' => 'Retina', 'macula' => 'Macula', 'vasos' => 'Vasos', 'periferia' => 'Periferia'] as $campo => $etiqueta): ?>
                        <label class="form-label mb-0"><?= $etiqueta ?></label>
                        <input type="text" name="fondo_ojo[od_<?= $campo ?>]" class="form-control form-control-sm" value="<?= $v('od_' . $campo) ?>">
                        <input type="text" name="fondo_ojo[oi_<?= $campo ?>]" class="form-control form-control-sm" value="<?= $v('oi_' . $campo) ?>">
                    <?php endforeach; ?>
                </div>
                <div class="mt-2"><label class="form-label mb-0 small">Observaciones</label><textarea name="fondo_ojo[observaciones]" class="form-control form-control-sm" rows="2"><?= $v('fondo_ojo_obs') ?></textarea></div>
            </div></div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secDiag">Diagnostico y tratamiento</button></h2>
            <div id="secDiag" class="accordion-collapse collapse" data-bs-parent="#acordeonExamen"><div class="accordion-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Diagnostico(s)</label>
                        <div data-filas-dinamicas data-nombre-campo="diagnosticos" data-placeholder="Ej. Miopia OU, Astigmatismo OD">
                            <?php foreach (($expediente['diagnosticos'] ?? []) as $d): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="diagnosticos[]" value="<?= e($d['diagnostico']) ?>">
                                    <button class="btn btn-outline-secondary" type="button" data-quitar-fila><i class="bi bi-x-lg"></i></button>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-agregar-fila><i class="bi bi-plus-lg"></i> Agregar diagnostico</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tratamiento(s) indicado(s)</label>
                        <div data-filas-dinamicas data-nombre-campo="tratamientos" data-placeholder="Ej. Lentes monofocales, Lagrimas artificiales c/8h">
                            <?php foreach (($expediente['tratamientos'] ?? []) as $t): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="tratamientos[]" value="<?= e($t['tratamiento']) ?>">
                                    <button class="btn btn-outline-secondary" type="button" data-quitar-fila><i class="bi bi-x-lg"></i></button>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-agregar-fila><i class="bi bi-plus-lg"></i> Agregar tratamiento</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Indicaciones</label>
                        <textarea name="indicaciones" class="form-control" rows="2"><?= $v('indicaciones') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones generales</label>
                        <textarea name="observaciones" class="form-control" rows="2"><?= $v('observaciones') ?></textarea>
                    </div>
                </div>
            </div></div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= e(Url::to('clientes/ver/' . $clienteId)) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar entrada clinica</button>
    </div>
</form>
