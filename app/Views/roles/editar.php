<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Permisos de ' . $rol['nombre']; ?>

<form method="POST" action="<?= e(Url::to('roles/actualizar/' . $rol['id'])) ?>">
    <?= Csrf::field() ?>

    <div class="card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Nombre del rol</label>
                <input type="text" name="nombre" class="form-control" value="<?= e($rol['nombre']) ?>" <?= $rol['es_sistema'] ? 'readonly' : '' ?>>
            </div>
            <div class="col-md-7">
                <label class="form-label">Descripcion</label>
                <input type="text" name="descripcion" class="form-control" value="<?= e($rol['descripcion'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 font-display mb-0">Matriz de permisos</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-marcar-todo">Marcar todo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-desmarcar-todo">Desmarcar todo</button>
            </div>
        </div>

        <div class="row g-3">
        <?php foreach ($matriz as $modulo => $permisos): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="border rounded p-3 h-100" style="border-color:var(--border) !important;">
                    <div class="fw-semibold small text-uppercase text-primary-brand mb-2"><?= e(str_replace('_', ' ', $modulo)) ?></div>
                    <?php foreach ($permisos as $p): ?>
                        <div class="form-check">
                            <input class="form-check-input casilla-permiso" type="checkbox" name="permisos[]" value="<?= (int) $p['id'] ?>" id="permiso-<?= (int) $p['id'] ?>" <?= $p['asignado'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="permiso-<?= (int) $p['id'] ?>"><?= e(ucfirst($p['accion'])) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= e(Url::to('roles')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar permisos</button>
    </div>
</form>

<script>
document.getElementById('btn-marcar-todo').addEventListener('click', function () {
    document.querySelectorAll('.casilla-permiso').forEach(function (c) { c.checked = true; });
});
document.getElementById('btn-desmarcar-todo').addEventListener('click', function () {
    document.querySelectorAll('.casilla-permiso').forEach(function (c) { c.checked = false; });
});
</script>
