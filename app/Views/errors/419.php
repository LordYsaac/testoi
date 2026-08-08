<?php use App\Core\Url; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 · Sesion expirada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(Url::asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center">
        <div class="display-1 font-display fw-bold" style="color:var(--warning, #c98a1c);">419</div>
        <p class="fs-5 mb-1">Su sesion o el formulario expiraron.</p>
        <p class="text-muted-soft mb-4">Vuelva atras, recargue la pagina e intente de nuevo.</p>
        <a href="javascript:history.back()" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
    </div>
</body>
</html>
