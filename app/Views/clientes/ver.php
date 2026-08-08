<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
$tituloPagina = $cliente['nombre_completo'];
?>

<div class="card p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar-circle" style="width:60px;height:60px;font-size:1.4rem;"><?= e(mb_strtoupper(mb_substr($cliente['nombres'], 0, 1))) ?></span>
            <div>
                <h2 class="h5 font-display mb-0"><?= e($cliente['nombre_completo']) ?></h2>
                <div class="small text-muted-soft font-mono"><?= e($cliente['codigo_cliente']) ?> · <?= $cliente['edad'] !== null ? (int) $cliente['edad'] . ' años · ' : '' ?><?= e($cliente['sexo'] === 'F' ? 'Femenino' : ($cliente['sexo'] === 'M' ? 'Masculino' : 'N/D')) ?></div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (Auth::puede('clientes.editar')): ?>
                <a href="<?= e(Url::to('clientes/editar/' . $cliente['id'])) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
            <?php endif; ?>
            <?php if (Auth::puede('expedientes.crear')): ?>
                <a href="<?= e(Url::to('expedientes/crear/' . $cliente['id'])) ?>" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-medical me-1"></i>Nueva consulta</a>
            <?php endif; ?>
            <?php if (Auth::puede('recetas.crear')): ?>
                <a href="<?= e(Url::to('recetas/crear/' . $cliente['id'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-prescription2 me-1"></i>Emitir receta</a>
            <?php endif; ?>
            <?php if (Auth::puede('facturas.crear')): ?>
                <a href="<?= e(Url::to('facturas/crear?cliente_id=' . $cliente['id'])) ?>" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>Facturar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Informacion</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antecedentes">Antecedentes</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clinico">Historial clinico <span class="badge badge-neutral ms-1"><?= count($historialClinico) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-recetas">Recetas <span class="badge badge-neutral ms-1"><?= count($recetas) ?></span></button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-info">
        <div class="card p-3">
            <div class="row g-3">
                <div class="col-md-4"><div class="small text-muted-soft">Telefono</div><div><?= e($cliente['telefono'] ?? '—') ?></div></div>
                <div class="col-md-4"><div class="small text-muted-soft">WhatsApp</div><div><?= e($cliente['whatsapp'] ?? '—') ?></div></div>
                <div class="col-md-4"><div class="small text-muted-soft">Correo</div><div><?= e($cliente['email'] ?? '—') ?></div></div>
                <div class="col-md-4"><div class="small text-muted-soft">Cedula/Pasaporte</div><div class="font-mono"><?= e($cliente['cedula_pasaporte'] ?? '—') ?></div></div>
                <div class="col-md-4"><div class="small text-muted-soft">Direccion</div><div><?= e($cliente['direccion'] ?? '—') ?></div></div>
                <div class="col-md-4"><div class="small text-muted-soft">Seguro medico</div><div><?= e($cliente['seguro_medico'] ?? 'Particular') ?></div></div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-antecedentes">
        <div class="card p-3">
            <form method="POST" action="<?= e(Url::to('clientes/antecedentes/' . $cliente['id'])) ?>">
                <?= Csrf::field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Antecedentes familiares</label>
                        <textarea name="familiares" class="form-control" rows="3"><?= e($antecedentes['familiares'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Antecedentes personales</label>
                        <textarea name="personales" class="form-control" rows="3"><?= e($antecedentes['personales'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quirurgicos</label>
                        <textarea name="quirurgicos" class="form-control" rows="3"><?= e($antecedentes['quirurgicos'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alergias</label>
                        <textarea name="alergias" class="form-control" rows="3"><?= e($antecedentes['alergias'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Medicamentos actuales</label>
                        <textarea name="medicamentos" class="form-control" rows="3"><?= e($antecedentes['medicamentos'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Guardar antecedentes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-clinico">
        <div class="card">
            <?php if (empty($historialClinico)): ?>
                <p class="text-muted-soft text-center py-4 mb-0">Sin entradas de historial clinico todavia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Motivo</th><th>Diagnostico(s)</th><th>Doctor</th><th class="text-end">Accion</th></tr></thead>
                        <tbody>
                        <?php foreach ($historialClinico as $h): ?>
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
    </div>

    <div class="tab-pane fade" id="tab-recetas">
        <div class="card">
            <?php if (empty($recetas)): ?>
                <p class="text-muted-soft text-center py-4 mb-0">Sin recetas emitidas todavia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Tipo de lente</th><th>Doctor</th><th>Estado</th><th class="text-end">Accion</th></tr></thead>
                        <tbody>
                        <?php foreach ($recetas as $r): ?>
                            <tr>
                                <td class="small"><?= fecha_hora($r['fecha']) ?></td>
                                <td><?= e($r['tipo_lente'] ?? '—') ?></td>
                                <td class="small"><?= e($r['doctor_nombre']) ?></td>
                                <td><span class="<?= estado_badge($r['estado']) ?>"><?= e(ucfirst($r['estado'])) ?></span></td>
                                <td class="text-end"><a href="<?= e(Url::to('recetas/ver/' . $r['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
