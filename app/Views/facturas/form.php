<?php use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Nueva factura'; ?>

<?php if (!$cajaAbierta): ?>
<div class="alert alert-warning small d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle"></i>
    <div>No tiene una caja abierta. Puede facturar igual, pero los pagos en efectivo no quedarán vinculados a ningún arqueo de caja. <a href="<?= e(Url::to('caja')) ?>">Abrir caja</a>.</div>
</div>
<?php endif; ?>

<form method="POST" action="<?= e(Url::to('facturas/guardar')) ?>" id="form-factura">
    <?= Csrf::field() ?>

    <div class="card p-3 mb-3">
        <div class="form-section-title">Datos de la venta</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Cliente</label>
                <?php if ($cliente): ?>
                    <input type="text" class="form-control" value="<?= e($cliente['nombres'] . ' ' . $cliente['apellidos'] . ' (' . $cliente['codigo_cliente'] . ')') ?>" disabled>
                    <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
                <?php else: ?>
                    <input type="text" id="buscador-clientes" class="form-control" data-base-url="<?= e(Url::base()) ?>" data-modo="seleccionar" placeholder="Escriba para buscar..." autocomplete="off">
                    <div id="resultados-clientes" class="list-group position-absolute" style="z-index:10;"></div>
                    <input type="hidden" name="cliente_id" id="cliente_id_seleccionado" required>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" id="select-tipo" class="form-select">
                    <option value="venta_producto">Venta de productos</option>
                    <option value="venta_medica">Venta / servicio médico</option>
                    <option value="mixta">Mixta</option>
                    <option value="cotizacion">Cotización (sin NCF ni inventario)</option>
                </select>
            </div>
            <div class="col-md-3" id="grupo-ncf">
                <label class="form-label">Comprobante fiscal</label>
                <select name="tipo_ncf" class="form-select">
                    <option value="">Sin NCF</option>
                    <option value="B01">B01 — Crédito fiscal</option>
                    <option value="B02" selected>B02 — Consumo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Condición</label>
                <select name="condicion_pago" class="form-select">
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-section-title mb-0 border-0 pb-0">Detalle</div>
            <button type="button" id="btn-agregar-linea" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar línea</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th style="width:28%">Producto</th><th style="width:22%">Descripción</th><th>Cant.</th><th>Precio</th><th>Desc.</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                <tbody id="cuerpo-lineas-factura"></tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between small py-1"><span>Subtotal</span><span class="font-mono" id="f-subtotal">RD$ 0.00</span></div>
                <div class="d-flex justify-content-between small py-1"><span>Descuento</span><span class="font-mono" id="f-descuento">RD$ 0.00</span></div>
                <div class="d-flex justify-content-between small py-1">
                    <span><div class="form-check d-inline-block"><input class="form-check-input" type="checkbox" name="aplica_itbis" value="1" id="chk-itbis-f" checked><label class="form-check-label" for="chk-itbis-f">ITBIS</label></div></span>
                    <span class="font-mono" id="f-itbis">RD$ 0.00</span>
                </div>
                <div class="d-flex justify-content-between fw-bold py-1 border-top"><span>Total</span><span class="font-mono" id="f-total">RD$ 0.00</span></div>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-section-title mb-0 border-0 pb-0">Pago</div>
            <button type="button" id="btn-agregar-pago" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar método</button>
        </div>
        <div id="cuerpo-pagos"></div>
        <div class="d-flex justify-content-between small mt-2">
            <span>Pagado: <strong class="font-mono" id="f-pagado">RD$ 0.00</strong></span>
            <span>Saldo pendiente: <strong class="font-mono" id="f-saldo">RD$ 0.00</strong></span>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" class="form-control" rows="2"></textarea>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= e(Url::to('facturas')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Registrar factura</button>
    </div>
</form>

<template id="plantilla-linea-factura">
    <tr>
        <td>
            <select class="form-select form-select-sm selector-producto-factura">
                <option value="">Servicio / sin producto</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" data-precio="<?= e((string) $p['precio']) ?>" data-nombre="<?= e($p['nombre']) ?>"><?= e($p['nombre'] . ' (' . $p['codigo'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="producto_id[]" class="campo-producto-id">
        </td>
        <td><input type="text" name="descripcion[]" class="form-control form-control-sm campo-descripcion" required></td>
        <td style="width:80px"><input type="number" step="0.01" name="cantidad[]" class="form-control form-control-sm campo-cantidad" value="1" min="0.01" required></td>
        <td style="width:110px"><input type="number" step="0.01" name="precio_unitario[]" class="form-control form-control-sm campo-precio" value="0" required></td>
        <td style="width:90px"><input type="number" step="0.01" name="descuento_linea[]" class="form-control form-control-sm campo-descuento" value="0"></td>
        <td class="text-end font-mono campo-subtotal-linea">RD$ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-linea-factura"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<template id="plantilla-pago">
    <div class="row g-2 mb-2 fila-pago">
        <div class="col-5">
            <select name="metodo_pago[]" class="form-select form-select-sm">
                <option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option><option value="transferencia">Transferencia</option><option value="cheque">Cheque</option>
            </select>
        </div>
        <div class="col-5"><input type="number" step="0.01" name="monto_pago[]" class="form-control form-control-sm campo-monto-pago" value="0"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger w-100 btn-quitar-pago"><i class="bi bi-x-lg"></i></button></div>
    </div>
</template>

<script>
(function () {
    var cuerpoLineas = document.getElementById('cuerpo-lineas-factura');
    var plantillaLinea = document.getElementById('plantilla-linea-factura');
    var cuerpoPagos = document.getElementById('cuerpo-pagos');
    var plantillaPago = document.getElementById('plantilla-pago');
    var itbisPorcentaje = <?= (float) ($empresa['itbis_porcentaje'] ?? 18) ?>;

    function fm(v) { return 'RD$ ' + v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function recalcular() {
        var subtotalBruto = 0, descuento = 0;
        cuerpoLineas.querySelectorAll('tr').forEach(function (fila) {
            var cant = parseFloat(fila.querySelector('.campo-cantidad').value) || 0;
            var precio = parseFloat(fila.querySelector('.campo-precio').value) || 0;
            var desc = parseFloat(fila.querySelector('.campo-descuento').value) || 0;
            var sub = (cant * precio) - desc;
            fila.querySelector('.campo-subtotal-linea').textContent = fm(sub);
            subtotalBruto += cant * precio;
            descuento += desc;
        });
        var base = Math.max(0, subtotalBruto - descuento);
        var aplicaItbis = document.getElementById('chk-itbis-f').checked;
        var itbis = aplicaItbis ? base * (itbisPorcentaje / 100) : 0;
        var total = base + itbis;

        document.getElementById('f-subtotal').textContent = fm(subtotalBruto);
        document.getElementById('f-descuento').textContent = fm(descuento);
        document.getElementById('f-itbis').textContent = fm(itbis);
        document.getElementById('f-total').textContent = fm(total);

        var pagado = 0;
        cuerpoPagos.querySelectorAll('.campo-monto-pago').forEach(function (i) { pagado += parseFloat(i.value) || 0; });
        document.getElementById('f-pagado').textContent = fm(pagado);
        document.getElementById('f-saldo').textContent = fm(Math.max(0, total - pagado));
    }

    function agregarLinea() {
        cuerpoLineas.appendChild(plantillaLinea.content.cloneNode(true));
        recalcular();
    }
    function agregarPago(metodoDefecto, montoDefecto) {
        var nodo = plantillaPago.content.cloneNode(true);
        if (montoDefecto !== undefined) nodo.querySelector('.campo-monto-pago').value = montoDefecto;
        cuerpoPagos.appendChild(nodo);
        recalcular();
    }

    cuerpoLineas.addEventListener('input', recalcular);
    cuerpoLineas.addEventListener('change', function (e) {
        if (e.target.classList.contains('selector-producto-factura')) {
            var opcion = e.target.selectedOptions[0];
            var fila = e.target.closest('tr');
            fila.querySelector('.campo-producto-id').value = opcion.value || '';
            if (opcion.value) {
                fila.querySelector('.campo-descripcion').value = opcion.dataset.nombre;
                fila.querySelector('.campo-precio').value = opcion.dataset.precio;
            }
            recalcular();
        }
    });
    cuerpoLineas.addEventListener('click', function (e) {
        if (e.target.closest('.btn-quitar-linea-factura')) { e.target.closest('tr').remove(); recalcular(); }
    });
    cuerpoPagos.addEventListener('input', recalcular);
    cuerpoPagos.addEventListener('click', function (e) {
        if (e.target.closest('.btn-quitar-pago')) { e.target.closest('.fila-pago').remove(); recalcular(); }
    });

    document.getElementById('btn-agregar-linea').addEventListener('click', agregarLinea);
    document.getElementById('btn-agregar-pago').addEventListener('click', function () { agregarPago(); });
    document.getElementById('chk-itbis-f').addEventListener('change', recalcular);

    document.getElementById('select-tipo').addEventListener('change', function () {
        var esCotizacion = this.value === 'cotizacion';
        document.getElementById('grupo-ncf').style.display = esCotizacion ? 'none' : '';
    });

    agregarLinea();
    agregarPago('efectivo');
})();
</script>
