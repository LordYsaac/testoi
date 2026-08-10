<?php use App\Core\Url; $tituloPagina = 'Reportes'; ?>

<div class="row g-3">
    <?php
    $reportes = [
        ['icon' => 'bi-graph-up-arrow', 'titulo' => 'Ventas por periodo', 'desc' => 'Facturacion diaria: subtotal, descuentos, ITBIS y total.', 'url' => 'reportes/ventas'],
        ['icon' => 'bi-award', 'titulo' => 'Productos mas vendidos', 'desc' => 'Ranking de productos por unidades y monto vendido.', 'url' => 'reportes/productos-mas-vendidos'],
        ['icon' => 'bi-people', 'titulo' => 'Clientes frecuentes', 'desc' => 'Clientes ordenados por cantidad de compras y monto total.', 'url' => 'reportes/clientes-frecuentes'],
        ['icon' => 'bi-box-seam', 'titulo' => 'Inventario valorizado', 'desc' => 'Existencias, valor en costo y en venta de todo el inventario.', 'url' => 'reportes/inventario'],
        ['icon' => 'bi-file-earmark-medical', 'titulo' => 'Pacientes atendidos', 'desc' => 'Consultas clinicas realizadas en un periodo.', 'url' => 'reportes/pacientes-atendidos'],
        ['icon' => 'bi-prescription2', 'titulo' => 'Recetas emitidas', 'desc' => 'Recetas opticas emitidas en un periodo, con su estado.', 'url' => 'reportes/recetas-emitidas'],
        ['icon' => 'bi-wallet2', 'titulo' => 'Cuentas por cobrar y pagar', 'desc' => 'Saldos pendientes de clientes y con proveedores.', 'url' => 'reportes/cuentas'],
    ];
    ?>
    <?php foreach ($reportes as $r): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= e(Url::to($r['url'])) ?>" class="card p-3 h-100 text-decoration-none d-flex flex-row align-items-start gap-3">
                <div class="kpi-icon bg-primary-light text-primary-brand flex-shrink-0"><i class="bi <?= $r['icon'] ?>"></i></div>
                <div>
                    <div class="fw-semibold text-body"><?= e($r['titulo']) ?></div>
                    <div class="small text-muted-soft"><?= e($r['desc']) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
