<?php use App\Core\Auth; use App\Core\Url; $tituloPagina = 'Panel principal'; ?>

<div class="row g-3 mb-2">
    <div class="col-6 col-lg-3">
        <div class="card card-kpi h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Ventas de hoy</div>
                    <div class="kpi-value"><?= moneda($kpis['ventas_hoy'] ?? 0) ?></div>
                </div>
                <div class="kpi-icon bg-primary-light text-primary-brand"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-kpi h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Ventas del mes</div>
                    <div class="kpi-value"><?= moneda($kpis['ventas_mes'] ?? 0) ?></div>
                </div>
                <div class="kpi-icon bg-primary-light text-primary-brand"><i class="bi bi-calendar3"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-kpi h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Citas de hoy</div>
                    <div class="kpi-value"><?= (int) ($kpis['citas_hoy'] ?? 0) ?></div>
                </div>
                <div class="kpi-icon" style="background:color-mix(in srgb, var(--accent) 18%, transparent); color:var(--accent-dark);"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-kpi h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Stock bajo</div>
                    <div class="kpi-value"><?= (int) ($kpis['productos_stock_bajo'] ?? 0) ?></div>
                </div>
                <div class="kpi-icon badge-danger"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card p-3 mb-3">
            <h2 class="h6 font-display mb-3">Ventas — ultimos 7 dias</h2>
            <canvas id="grafico-ventas" height="110"></canvas>
        </div>

        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 font-display mb-0">Citas de hoy</h2>
                <span class="badge badge-neutral"><?= count($citasHoy) ?></span>
            </div>
            <?php if (empty($citasHoy)): ?>
                <p class="text-muted-soft small mb-0">No hay citas agendadas para hoy.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Hora</th><th>Paciente</th><th>Doctor</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($citasHoy as $cita): ?>
                            <tr>
                                <td class="font-mono"><?= e(substr($cita['hora'], 0, 5)) ?></td>
                                <td><?= e($cita['paciente']) ?></td>
                                <td><?= e($cita['doctor'] ?? '—') ?></td>
                                <td><span class="<?= estado_badge($cita['estado']) ?>"><?= e(ucfirst($cita['estado'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 font-display mb-0"><i class="bi bi-bell text-primary-brand"></i> Notificaciones</h2>
            </div>
            <?php if (empty($notificaciones)): ?>
                <p class="text-muted-soft small mb-0">No tiene notificaciones pendientes.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($notificaciones as $n): ?>
                        <li class="mb-2 pb-2 border-bottom">
                            <div class="fw-semibold small"><?= e($n['titulo']) ?></div>
                            <div class="small text-muted-soft"><?= e($n['mensaje']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card p-3 mb-3">
            <h2 class="h6 font-display mb-3"><i class="bi bi-box-seam text-primary-brand"></i> Stock bajo</h2>
            <?php if (empty($stockBajo)): ?>
                <p class="text-muted-soft small mb-0">Todo el inventario esta por encima del minimo.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($stockBajo as $p): ?>
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <span><?= e($p['nombre']) ?></span>
                            <span class="badge-danger px-2 py-1 rounded"><?= (int) $p['stock_actual'] ?>/<?= (int) $p['stock_minimo'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (Auth::puede('cuentas_cobrar.ver')): ?>
        <div class="card p-3">
            <h2 class="h6 font-display mb-3"><i class="bi bi-cash-coin text-primary-brand"></i> Clientes morosos</h2>
            <?php if (empty($clientesMorosos)): ?>
                <p class="text-muted-soft small mb-0">No hay cuentas vencidas.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($clientesMorosos as $c): ?>
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <span><?= e($c['cliente']) ?></span>
                            <span class="text-danger fw-semibold"><?= moneda($c['saldo_total']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var datos = <?= json_encode($ventasUltimos7Dias, JSON_UNESCAPED_UNICODE) ?>;
    var etiquetas = [], valores = [];
    var mapa = {};
    datos.forEach(function (d) { mapa[d.dia] = parseFloat(d.total); });
    for (var i = 6; i >= 0; i--) {
        var f = new Date();
        f.setDate(f.getDate() - i);
        var iso = f.toISOString().slice(0, 10);
        etiquetas.push(f.toLocaleDateString('es-DO', { weekday: 'short', day: 'numeric' }));
        valores.push(mapa[iso] || 0);
    }
    var ctx = document.getElementById('grafico-ventas');
    var estiloOscuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Ventas (RD$)',
                data: valores,
                borderColor: '#0e6e6e',
                backgroundColor: 'rgba(14,110,110,.12)',
                fill: true,
                tension: .35,
                pointRadius: 3
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: estiloOscuro ? '#244047' : '#eef4f4' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
