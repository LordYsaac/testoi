<?php use App\Core\Url; $tituloPagina = 'Cuentas por cobrar'; ?>

<div class="card">
    <?php if (empty($morosos)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">No hay cuentas pendientes de cobro. 🎉</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Cliente</th><th>Telefono</th><th class="text-end">Saldo pendiente</th><th>Factura mas antigua</th><th class="text-end">Dias vencido</th><th class="text-end">Accion</th></tr></thead>
                <tbody>
                <?php foreach ($morosos as $m): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($m['cliente']) ?> <span class="text-muted-soft small font-mono"><?= e($m['codigo_cliente']) ?></span></td>
                        <td class="small"><?= e($m['telefono'] ?? '—') ?></td>
                        <td class="text-end font-mono text-danger fw-semibold"><?= moneda($m['saldo_total']) ?></td>
                        <td class="small"><?= fecha_larga($m['factura_mas_antigua']) ?></td>
                        <td class="text-end">
                            <span class="<?= (int) $m['dias_vencido'] > 30 ? 'badge-danger' : 'badge-warning' ?> px-2 py-1 rounded"><?= (int) $m['dias_vencido'] ?> dias</span>
                        </td>
                        <td class="text-end"><a href="<?= e(Url::to('cuentas-por-cobrar/cliente/' . $m['id'])) ?>" class="btn btn-sm btn-outline-primary">Gestionar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
