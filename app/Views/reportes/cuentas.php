<?php use App\Core\Url; $tituloPagina = 'Cuentas por cobrar y pagar'; ?>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="form-section-title mb-0 border-0 pb-0">Por cobrar (clientes)</div>
                <a href="<?= e(Url::to('reportes/cuentas?formato=csv&tipo=por_cobrar')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
            </div>
            <?php if (empty($porCobrar)): ?>
                <p class="text-muted-soft text-center py-4 mb-0">Sin cuentas pendientes.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Cliente</th><th class="text-end">Saldo</th><th class="text-end">Dias</th></tr></thead>
                        <tbody>
                        <?php foreach ($porCobrar as $c): ?>
                            <tr>
                                <td class="small"><?= e($c['cliente']) ?></td>
                                <td class="text-end font-mono text-danger"><?= moneda($c['saldo_total']) ?></td>
                                <td class="text-end small"><?= (int) $c['dias_vencido'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="form-section-title mb-0 border-0 pb-0">Por pagar (proveedores)</div>
                <a href="<?= e(Url::to('reportes/cuentas?formato=csv&tipo=por_pagar')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
            </div>
            <?php if (empty($porPagar)): ?>
                <p class="text-muted-soft text-center py-4 mb-0">Sin cuentas pendientes.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Proveedor</th><th class="text-end">Saldo</th><th class="text-end">Facturas</th></tr></thead>
                        <tbody>
                        <?php foreach ($porPagar as $p): ?>
                            <tr>
                                <td class="small"><?= e($p['proveedor']) ?></td>
                                <td class="text-end font-mono text-danger"><?= moneda($p['saldo_total']) ?></td>
                                <td class="text-end small"><?= (int) $p['facturas_pendientes'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
