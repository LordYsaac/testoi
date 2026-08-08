<?php use App\Core\Url; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 · Acceso denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(Url::asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center">
        <div class="display-1 font-display fw-bold" style="color:var(--danger, #c94a3f);">403</div>
        <p class="fs-5 mb-1">No tiene permiso para acceder a esta seccion.</p>
        <p class="text-muted-soft mb-4">Si considera que esto es un error, contacte a su administrador.</p>
        <a href="<?= e(Url::to('dashboard')) ?>" class="btn btn-primary"><i class="bi bi-house me-1"></i> Ir al panel principal</a>
    </div>
</body>
</html>
