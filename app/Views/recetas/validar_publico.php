<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validacion de receta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
    <?php if (!$receta): ?>
        <div class="card p-4 text-center border-danger">
            <i class="bi bi-x-circle text-danger" style="font-size:2.5rem;"></i>
            <h1 class="h5 mt-2">Codigo no encontrado</h1>
            <p class="text-muted mb-0">Esta receta no existe en nuestro sistema o el codigo es incorrecto.</p>
        </div>
    <?php elseif ($receta['estado'] === 'anulada'): ?>
        <div class="card p-4 text-center border-danger">
            <i class="bi bi-exclamation-triangle text-danger" style="font-size:2.5rem;"></i>
            <h1 class="h5 mt-2">Receta anulada</h1>
            <p class="text-muted mb-0">Esta receta fue anulada y ya no es valida.</p>
        </div>
    <?php else: ?>
        <div class="card p-4 border-success">
            <div class="text-center mb-3">
                <i class="bi bi-patch-check text-success" style="font-size:2.5rem;"></i>
                <h1 class="h5 mt-2 mb-0">Receta valida</h1>
                <p class="text-muted small">Emitida el <?= fecha_larga($receta['fecha']) ?></p>
            </div>
            <table class="table table-sm">
                <tr><th>Paciente</th><td><?= e($receta['cliente_nombres'] . ' ' . $receta['cliente_apellidos']) ?></td></tr>
                <tr><th>Especialista</th><td>Dr(a). <?= e($receta['doctor_nombre']) ?></td></tr>
                <tr><th>Tipo de lente</th><td><?= e($receta['tipo_lente'] ?? '—') ?></td></tr>
            </table>
            <p class="text-muted small mb-0 text-center">Esta pagina solo confirma la validez de la receta; para ver la graduacion completa, presente el documento fisico o consulte con la optica emisora.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
