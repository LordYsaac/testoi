<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Registrar compra'; ?>

<form method="POST" action="<?= e(Url::to('compras/guardar')) ?>" id="form-compra">
    <?= Csrf::field() ?>

    <div class="card p-3 mb-3">
        <div class="form-section-title">Datos de la compra</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">No. de factura del proveedor</label>
                <input type="text" name="numero_factura_proveedor" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-section-title mb-0 border-0 pb-0">Productos</div>
            <button type="button" id="btn-agregar-linea" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar linea</button>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle" id="tabla-lineas-compra">
                <thead><tr><th style="width:40%">Producto</th><th>Cantidad</th><th>Costo unitario</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                <tbody id="cuerpo-lineas-compra"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Subtotal</td>
                        <td class="text-end font-mono" id="total-subtotal">RD$ 0.00</td><td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">
                            <div class="form-check d-inline-block">
                                <input class="form-check-input" type="checkbox" name="aplica_itbis" value="1" id="chk-itbis" checked>
                                <label class="form-check-label" for="chk-itbis">ITBIS (<?= e($empresa['itbis_porcentaje'] ?? '18') ?>%)</label>
                            </div>
                        </td>
                        <td class="text-end font-mono" id="total-itbis">RD$ 0.00</td><td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end font-mono fw-bold" id="total-general">RD$ 0.00</td><td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-muted-soft small mb-0">Al guardar, cada línea genera automáticamente un movimiento de entrada en el inventario.</p>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= e(Url::to('compras')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Registrar compra</button>
    </div>
</form>

<template id="plantilla-linea-compra">
    <tr>
        <td>
            <select name="producto_id[]" class="form-select form-select-sm selector-producto" required>
                <option value="">Seleccione un producto...</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" data-costo="<?= e((string) $p['costo']) ?>"><?= e($p['nombre'] . ' (' . $p['codigo'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="cantidad[]" class="form-control form-control-sm campo-cantidad" min="1" value="1" required></td>
        <td><input type="number" step="0.01" name="costo_unitario[]" class="form-control form-control-sm campo-costo" min="0" value="0" required></td>
        <td class="text-end font-mono campo-subtotal-linea">RD$ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-linea"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var cuerpo = document.getElementById('cuerpo-lineas-compra');
    var plantilla = document.getElementById('plantilla-linea-compra');
    var itbisPorcentaje = <?= (float) ($empresa['itbis_porcentaje'] ?? 18) ?>;

    function formatoMoneda(valor) {
        return 'RD$ ' + valor.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcularTotales() {
        var subtotal = 0;
        cuerpo.querySelectorAll('tr').forEach(function (fila) {
            var cantidad = parseFloat(fila.querySelector('.campo-cantidad').value) || 0;
            var costo = parseFloat(fila.querySelector('.campo-costo').value) || 0;
            var sub = cantidad * costo;
            fila.querySelector('.campo-subtotal-linea').textContent = formatoMoneda(sub);
            subtotal += sub;
        });
        var aplicaItbis = document.getElementById('chk-itbis').checked;
        var itbis = aplicaItbis ? subtotal * (itbisPorcentaje / 100) : 0;
        document.getElementById('total-subtotal').textContent = formatoMoneda(subtotal);
        document.getElementById('total-itbis').textContent = formatoMoneda(itbis);
        document.getElementById('total-general').textContent = formatoMoneda(subtotal + itbis);
    }

    function agregarLinea() {
        var nodo = plantilla.content.cloneNode(true);
        cuerpo.appendChild(nodo);
        recalcularTotales();
    }

    cuerpo.addEventListener('input', recalcularTotales);
    document.getElementById('chk-itbis').addEventListener('change', recalcularTotales);

    cuerpo.addEventListener('change', function (e) {
        if (e.target.classList.contains('selector-producto')) {
            var opcion = e.target.selectedOptions[0];
            var fila = e.target.closest('tr');
            if (opcion && opcion.dataset.costo) {
                fila.querySelector('.campo-costo').value = opcion.dataset.costo;
            }
            recalcularTotales();
        }
    });

    cuerpo.addEventListener('click', function (e) {
        if (e.target.closest('.btn-quitar-linea')) {
            e.target.closest('tr').remove();
            recalcularTotales();
        }
    });

    document.getElementById('btn-agregar-linea').addEventListener('click', agregarLinea);
    document.getElementById('form-compra').addEventListener('submit', function (e) {
        if (!cuerpo.querySelector('tr')) {
            e.preventDefault();
            alert('Agregue al menos un producto a la compra.');
        }
    });

    agregarLinea(); // primera linea vacia por defecto
})();
</script>
