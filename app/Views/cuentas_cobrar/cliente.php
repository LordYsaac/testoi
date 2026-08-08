<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Cuentas por cobrar — ' . $cliente['nombres']; ?>

<div class="card p-3 mb-3">
    <h2 class="h6 mb-0"><?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></h2>
    <span class="small text-muted-soft font-mono"><?= e($cliente['codigo_cliente']) ?></span>
</div>

<div class="card">
    <div class="p-3 pb-0"><div class="form-section-title">Facturas pendientes</div></div>
    <?php if (empty($pendientes)): ?>
        <p class="text-muted-soft text-center py-4 mb-0">Este cliente no tiene facturas pendientes.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th style="width:280px">Registrar abono</th></tr></thead>
                <tbody>
                <?php foreach ($pendientes as $f): ?>
                    <tr>
                        <td class="small"><?= fecha_larga($f['fecha']) ?></td>
                        <td class="text-end font-mono"><?= moneda($f['total']) ?></td>
                        <td class="text-end font-mono text-danger"><?= moneda($f['saldo_pendiente']) ?></td>
                        <td>
                            <form method="POST" action="<?= e(Url::to('cuentas-por-cobrar/abonar/' . $f['id'])) ?>" class="input-group input-group-sm">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="cliente_id" value="<?= (int) $clienteId ?>">
                                <input type="number" step="0.01" name="monto" class="form-control" max="<?= e((string) $f['saldo_pendiente']) ?>" placeholder="Monto" required>
                                <button class="btn btn-primary">Abonar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
