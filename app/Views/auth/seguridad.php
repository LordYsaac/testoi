<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Seguridad de la cuenta'; ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-7">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="h6 font-display mb-1"><i class="bi bi-shield-check text-primary-brand me-1"></i> Verificacion en dos pasos (2FA)</h2>
                    <p class="text-muted-soft small mb-0">Pide un codigo adicional de una app autenticadora (Google Authenticator, Authy...) al iniciar sesion.</p>
                </div>
                <span class="<?= $usuario['two_factor_activo'] ? 'badge-success' : 'badge-neutral' ?> px-2 py-1 rounded"><?= $usuario['two_factor_activo'] ? 'Activo' : 'Inactivo' ?></span>
            </div>

            <?php if ($usuario['two_factor_activo'] && !$secretoTemporal): ?>
                <p class="small text-muted-soft">Su cuenta ya esta protegida con verificacion en dos pasos.</p>
                <form method="POST" action="<?= e(Url::to('seguridad/2fa/desactivar')) ?>" data-confirmar="¿Desactivar la verificacion en dos pasos?">
                    <?= Csrf::field() ?>
                    <label class="form-label small">Confirme su contraseña para desactivarla</label>
                    <div class="input-group">
                        <input type="password" name="password_actual" class="form-control" required>
                        <button class="btn btn-outline-danger">Desactivar</button>
                    </div>
                </form>

            <?php elseif ($qrUrl): ?>
                <div class="text-center mb-3">
                    <img src="<?= e($qrUrl) ?>" alt="Codigo QR" class="border rounded p-2 mb-2">
                    <p class="small text-muted-soft mb-1">Escanee este codigo con Google Authenticator, Authy o similar.</p>
                    <p class="small">¿No puede escanear? Ingrese esta clave manualmente:</p>
                    <p class="font-mono fw-semibold" style="letter-spacing:.1rem;"><?= e($secretoTemporal) ?></p>
                </div>
                <form method="POST" action="<?= e(Url::to('seguridad/2fa/activar')) ?>">
                    <?= Csrf::field() ?>
                    <label class="form-label small">Ingrese el codigo de 6 digitos que muestra la app para confirmar</label>
                    <div class="input-group">
                        <input type="text" name="codigo" class="form-control font-mono" inputmode="numeric" maxlength="6" placeholder="000000" required autofocus>
                        <button class="btn btn-primary">Activar</button>
                    </div>
                </form>

            <?php else: ?>
                <p class="small text-muted-soft">Su cuenta no tiene verificacion en dos pasos activada.</p>
                <form method="POST" action="<?= e(Url::to('seguridad/2fa/generar')) ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-qr-code me-1"></i>Activar verificacion en dos pasos</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card p-4 mt-3">
            <h2 class="h6 font-display mb-2"><i class="bi bi-key text-primary-brand me-1"></i> Contraseña</h2>
            <a href="<?= e(Url::to('cambiar-password')) ?>" class="btn btn-outline-secondary btn-sm">Cambiar mi contraseña</a>
        </div>
    </div>
</div>
