<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
$tituloPagina = 'Citas del dia';
$diaSemana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'][(int) date('w', strtotime($fecha))];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 font-display mb-0"><?= e($diaSemana) ?></h2>
        <span class="text-muted-soft"><?= fecha_larga($fecha) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(Url::to('citas')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3 me-1"></i>Ver calendario</a>
        <?php if (Auth::puede('citas.crear')): ?>
            <a href="<?= e(Url::to('citas/crear?fecha=' . $fecha)) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nueva cita</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <?php if (empty($citas)): ?>
        <p class="text-muted-soft text-center py-5 mb-0">No hay citas agendadas para este dia.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Hora</th><th>Paciente</th><th>Doctor</th><th>Motivo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($citas as $c): ?>
                    <tr>
                        <td class="font-mono fw-semibold"><?= e(substr($c['hora'], 0, 5)) ?></td>
                        <td>
                            <a href="<?= e(Url::to('clientes/ver/' . $c['cliente_id'])) ?>" class="text-decoration-none"><?= e($c['paciente']) ?></a>
                            <div class="small text-muted-soft"><?= e($c['telefono'] ?? '') ?></div>
                        </td>
                        <td class="small"><?= e($c['doctor_nombre'] ?? '—') ?></td>
                        <td class="small"><?= e($c['motivo'] ?? '—') ?></td>
                        <td><span class="<?= estado_badge($c['estado']) ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Estado</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'] as $valor => $etiqueta): ?>
                                        <li>
                                            <form method="POST" action="<?= e(Url::to('citas/estado/' . $c['id'])) ?>">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="estado" value="<?= $valor ?>">
                                                <button type="submit" class="dropdown-item <?= $c['estado'] === $valor ? 'active' : '' ?>"><?= $etiqueta ?></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php if (Auth::puede('citas.editar')): ?>
                            <a href="<?= e(Url::to('citas/editar/' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (Auth::puede('expedientes.crear')): ?>
                            <a href="<?= e(Url::to('expedientes/crear/' . $c['cliente_id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Iniciar consulta"><i class="bi bi-file-earmark-medical"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
