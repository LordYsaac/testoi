<?php use App\Core\Csrf; use App\Core\Url; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion en dos pasos · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(Url::asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="auth-screen">
    <div class="auth-card">
        <div class="text-center mb-3">
            <div class="auth-mark"><i class="bi bi-shield-lock" style="color:#fff;position:relative;top:14px;left:17px;font-size:1.1rem;"></i></div>
            <h1 class="h5 font-display fw-semibold mb-0">Verificacion en dos pasos</h1>
            <p class="text-muted-soft small mb-0">Ingrese el codigo de 6 digitos de su app autenticadora</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle"></i><div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(Url::to('verificar-2fa')) ?>" autocomplete="off">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <input type="text" class="form-control form-control-lg text-center font-mono" name="codigo" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autofocus style="letter-spacing:.5rem;">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Verificar</button>
        </form>

        <p class="text-center small mt-3 mb-0"><a href="<?= e(Url::to('login')) ?>" class="text-muted-soft">Cancelar e iniciar sesion de nuevo</a></p>
    </div>
</div>
</body>
</html>
