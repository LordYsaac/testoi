<?php
use App\Core\Csrf;
use App\Core\Url;
$esEdicion = !empty($producto['id']);
$tituloPagina = $esEdicion ? 'Editar producto' : 'Nuevo producto';
$accion = $esEdicion ? Url::to('productos/actualizar/' . $producto['id']) : Url::to('productos/guardar');
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="card p-4">
            <form method="POST" action="<?= e($accion) ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <div class="form-section-title">Identificacion</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3 text-center">
                        <div class="avatar-circle mx-auto mb-2" style="width:72px;height:72px;border-radius:.65rem;"><i class="bi bi-box-seam fs-3"></i></div>
                        <input type="file" name="imagen" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($producto['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Codigo</label>
                                <input type="text" name="codigo" class="form-control font-mono <?= isset($errores['codigo']) ? 'is-invalid' : '' ?>" value="<?= e($producto['codigo'] ?? $siguienteCodigo) ?>" <?= $esEdicion ? 'disabled' : '' ?> placeholder="<?= e($siguienteCodigo) ?>">
                                <?php if (isset($errores['codigo'])): ?><div class="invalid-feedback"><?= e($errores['codigo']) ?></div><?php endif; ?>
                                <div class="form-text">Vacio = automatico</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Codigo de barras</label>
                                <input type="text" name="codigo_barras" class="form-control font-mono" value="<?= e($producto['codigo_barras'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoria</label>
                                <select name="categoria_id" class="form-select <?= isset($errores['categoria_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($producto['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" value="<?= e($producto['marca'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" value="<?= e($producto['modelo'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section-title">Detalles</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label">Color</label><input type="text" name="color" class="form-control" value="<?= e($producto['color'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Material</label><input type="text" name="material" class="form-control" value="<?= e($producto['material'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Ubicacion</label><input type="text" name="ubicacion" class="form-control" value="<?= e($producto['ubicacion'] ?? '') ?>" placeholder="Vitrina 2, Anaquel B"></div>
                    <div class="col-md-3"><label class="form-label">Lote</label><input type="text" name="lote" class="form-control" value="<?= e($producto['lote'] ?? '') ?>"></div>
                    <div class="col-md-4">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($proveedores as $pr): ?>
                                <option value="<?= (int) $pr['id'] ?>" <?= (int) ($producto['proveedor_id'] ?? 0) === (int) $pr['id'] ? 'selected' : '' ?>><?= e($pr['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Fecha de vencimiento</label><input type="date" name="fecha_vencimiento" class="form-control" value="<?= e($producto['fecha_vencimiento'] ?? '') ?>"></div>
                    <?php if ($esEdicion): ?>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= ($producto['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($producto['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="descontinuado" <?= ($producto['estado'] ?? '') === 'descontinuado' ? 'selected' : '' ?>>Descontinuado</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-section-title">Precios e inventario</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label">Costo</label><input type="number" step="0.01" name="costo" class="form-control" value="<?= e($producto['costo'] ?? '0') ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Precio de venta</label><input type="number" step="0.01" name="precio" class="form-control" value="<?= e($producto['precio'] ?? '0') ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Stock minimo</label><input type="number" name="stock_minimo" class="form-control" value="<?= e($producto['stock_minimo'] ?? '0') ?>"></div>
                    <?php if (!$esEdicion): ?>
                    <div class="col-md-3">
                        <label class="form-label">Existencia inicial</label>
                        <input type="number" name="stock_inicial" class="form-control" value="0" min="0">
                        <div class="form-text">Genera un movimiento de entrada</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(Url::to('productos')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar producto</button>
                </div>
            </form>
        </div>
    </div>
</div>
