<?php
use App\Core\Csrf;
use App\Core\Url;
$esEdicion = !empty($proveedor['id']);
$tituloPagina = $esEdicion ? 'Editar proveedor' : 'Nuevo proveedor';
$accion = $esEdicion ? Url::to('proveedores/actualizar/' . $proveedor['id']) : Url::to('proveedores/guardar');
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card p-4">
            <form method="POST" action="<?= e($accion) ?>">
                <?= Csrf::field() ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombre / Razon social</label>
                        <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($proveedor['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Persona de contacto</label>
                        <input type="text" name="contacto_nombre" class="form-control" value="<?= e($proveedor['contacto_nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RNC</label>
                        <input type="text" name="rnc" class="form-control" value="<?= e($proveedor['rnc'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= e($proveedor['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= e($proveedor['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Direccion</label>
                        <input type="text" name="direccion" class="form-control" value="<?= e($proveedor['direccion'] ?? '') ?>">
                    </div>
                    <?php if ($esEdicion): ?>
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= ($proveedor['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($proveedor['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= e(Url::to('proveedores')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>
