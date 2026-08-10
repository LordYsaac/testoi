<?php
use App\Core\Csrf;
use App\Core\Url;
$esEdicion = !empty($cita['id']);
$tituloPagina = $esEdicion ? 'Editar cita' : 'Nueva cita';
$accion = $esEdicion ? Url::to('citas/actualizar/' . $cita['id']) : Url::to('citas/guardar');
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <form method="POST" action="<?= e($accion) ?>">
                <?= Csrf::field() ?>

                <?php if (!$esEdicion): ?>
                    <div class="mb-3 position-relative">
                        <label class="form-label">Paciente</label>
                        <?php if ($cliente): ?>
                            <input type="text" class="form-control" value="<?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?>" disabled>
                            <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
                        <?php else: ?>
                            <input type="text" id="buscador-clientes" class="form-control" data-base-url="<?= e(Url::base()) ?>" data-modo="seleccionar" placeholder="Escriba para buscar..." autocomplete="off">
                            <div id="resultados-clientes" class="list-group position-absolute" style="z-index:10; width:100%;"></div>
                            <input type="hidden" name="cliente_id" id="cliente_id_seleccionado" required>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Paciente</label>
                        <input type="text" class="form-control" value="<?= e($cita['paciente']) ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= e($fecha) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora" class="form-control" value="<?= e($cita['hora'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Doctor / Optometra</label>
                        <select name="doctor_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?= (int) $d['id'] ?>" <?= (int) ($cita['doctor_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="motivo" class="form-control" value="<?= e($cita['motivo'] ?? '') ?>" placeholder="Ej. Control anual, ajuste de montura...">
                    </div>
                    <?php if ($esEdicion): ?>
                    <div class="col-12">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <?php foreach (['pendiente','confirmada','finalizada','cancelada'] as $estado): ?>
                                <option value="<?= $estado ?>" <?= ($cita['estado'] ?? '') === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"><?= e($cita['notas'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(Url::to('citas/dia/' . $fecha)) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
