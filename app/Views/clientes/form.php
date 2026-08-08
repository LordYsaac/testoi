<?php
use App\Core\Csrf;
use App\Core\Url;
$esEdicion = !empty($cliente['id']);
$tituloPagina = $esEdicion ? 'Editar cliente' : 'Nuevo cliente';
$accion = $esEdicion ? Url::to('clientes/actualizar/' . $cliente['id']) : Url::to('clientes/guardar');
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="card p-4">
            <form method="POST" action="<?= e($accion) ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <div class="form-section-title">Datos personales</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-2 text-center">
                        <span class="avatar-circle mx-auto mb-2" style="width:72px;height:72px;font-size:1.5rem;">
                            <?= e(mb_strtoupper(mb_substr($cliente['nombres'] ?? 'C', 0, 1))) ?>
                        </span>
                        <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-md-10">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" name="nombres" class="form-control <?= isset($errores['nombres']) ? 'is-invalid' : '' ?>" value="<?= e($cliente['nombres'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control <?= isset($errores['apellidos']) ? 'is-invalid' : '' ?>" value="<?= e($cliente['apellidos'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sexo</label>
                                <select name="sexo" class="form-select">
                                    <option value="">—</option>
                                    <option value="F" <?= ($cliente['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                                    <option value="M" <?= ($cliente['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="form-control" value="<?= e($cliente['fecha_nacimiento'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cedula/Pasaporte</label>
                                <input type="text" name="cedula_pasaporte" class="form-control <?= isset($errores['cedula_pasaporte']) ? 'is-invalid' : '' ?>" value="<?= e($cliente['cedula_pasaporte'] ?? '') ?>">
                                <?php if (isset($errores['cedula_pasaporte'])): ?><div class="invalid-feedback"><?= e($errores['cedula_pasaporte']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section-title">Contacto</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= e($cliente['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= e($cliente['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Correo electronico</label>
                        <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= e($cliente['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Direccion</label>
                        <input type="text" name="direccion" class="form-control" value="<?= e($cliente['direccion'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-section-title">Seguro medico</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Aseguradora / ARS</label>
                        <select name="seguro_medico_id" class="form-select">
                            <option value="">Particular (sin seguro)</option>
                            <?php foreach ($seguros as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= (int) ($cliente['seguro_medico_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($esEdicion): ?>
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= ($cliente['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($cliente['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"><?= e($cliente['observaciones'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(Url::to('clientes')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>
