<?php use App\Core\Csrf; use App\Core\Url; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion · <?= e(APP_NAME) ?></title>
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
            <div class="auth-mark"></div>
            <h1 class="h5 font-display fw-semibold mb-0"><?= e(APP_NAME) ?></h1>
            <p class="text-muted-soft small mb-0">Gestion integral de optica y clinica oftalmologica</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle"></i><div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(Url::to('login')) ?>" autocomplete="off">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label" for="username">Usuario</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
        </form>

        <p class="text-center small text-muted-soft mt-3 mb-0">Usuario semilla: <code>admin</code> / <code>Admin#2026</code></p>
    </div>
</div>
</body>
</html>
