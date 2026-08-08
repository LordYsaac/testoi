<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Roles y permisos'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted-soft mb-0">Cada rol agrupa permisos por modulo y accion.</p>
    <?php if (Auth::puede('roles.crear')): ?>
        <a href="<?= e(Url::to('roles/crear')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo rol</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php foreach ($roles as $r): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card p-3 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2 class="h6 font-display mb-0"><?= e($r['nombre']) ?></h2>
                    <?php if ($r['es_sistema']): ?><span class="badge badge-neutral">Base</span><?php endif; ?>
                </div>
                <p class="small text-muted-soft flex-grow-1"><?= e($r['descripcion'] ?? 'Sin descripcion.') ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted-soft"><i class="bi bi-people me-1"></i><?= (int) $r['total_usuarios'] ?> usuario(s)</span>
                    <?php if (Auth::puede('roles.editar')): ?>
                        <a href="<?= e(Url::to('roles/editar/' . $r['id'])) ?>" class="btn btn-sm btn-outline-primary">Ver permisos</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
