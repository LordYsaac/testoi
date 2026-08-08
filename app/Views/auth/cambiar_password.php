<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Cambiar contraseña'; ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card p-4">
            <h2 class="h5 font-display mb-1">Actualice su contraseña</h2>
            <p class="text-muted-soft small mb-3">Por seguridad, debe establecer una nueva contraseña antes de continuar.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= e(Url::to('cambiar-password')) ?>">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" name="password_actual" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" name="password_nueva" class="form-control" minlength="8" required>
                    <div class="form-text">Minimo 8 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmacion" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Guardar y continuar</button>
            </form>
        </div>
    </div>
</div>
