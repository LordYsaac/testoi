<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Nuevo rol'; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <form method="POST" action="<?= e(Url::to('roles/guardar')) ?>">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Nombre del rol</label>
                    <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errores['nombre'])): ?><div class="invalid-feedback"><?= e($errores['nombre']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(Url::to('roles')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Crear y asignar permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>
