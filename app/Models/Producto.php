<?php

namespace App\Models;

use App\Core\Model;

class Producto extends Model
{
    protected string $table = 'productos';

    public function listar(string $busqueda = '', int $categoriaId = 0, string $estado = 'activo', int $limite = 20, int $pagina = 1): array
    {
        $offset = max(0, ($pagina - 1) * $limite);
        $condiciones = [];
        $params = [];

        if ($busqueda !== '') {
            $condiciones[] = '(p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda OR p.codigo_barras LIKE :busqueda OR p.marca LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($categoriaId > 0) {
            $condiciones[] = 'p.categoria_id = :categoria_id';
            $params['categoria_id'] = $categoriaId;
        }
        if ($estado !== '') {
            $condiciones[] = 'p.estado = :estado';
            $params['estado'] = $estado;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, pr.nombre AS proveedor_nombre
                  FROM productos p
                  JOIN categorias_productos c ON c.id = p.categoria_id
                  LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                  {$where}
                 ORDER BY p.nombre
                 LIMIT {$limite} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalListar(string $busqueda = '', int $categoriaId = 0, string $estado = 'activo'): int
    {
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = '(nombre LIKE :busqueda OR codigo LIKE :busqueda OR codigo_barras LIKE :busqueda OR marca LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($categoriaId > 0) {
            $condiciones[] = 'categoria_id = :categoria_id';
            $params['categoria_id'] = $categoriaId;
        }
        if ($estado !== '') {
            $condiciones[] = 'estado = :estado';
            $params['estado'] = $estado;
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM productos {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    public function conDetalle(int $id): array|false
    {
        $sql = 'SELECT p.*, c.nombre AS categoria_nombre, pr.nombre AS proveedor_nombre
                  FROM productos p
                  JOIN categorias_productos c ON c.id = p.categoria_id
                  LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                 WHERE p.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function kardex(int $productoId, int $limite = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vista_kardex WHERE producto_id = :id ORDER BY fecha DESC, id DESC LIMIT ' . (int) $limite);
        $stmt->execute(['id' => $productoId]);
        return $stmt->fetchAll();
    }

    public function categorias(): array
    {
        return $this->db->query("SELECT id, nombre FROM categorias_productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }

    public function proveedoresActivos(): array
    {
        return $this->db->query("SELECT id, nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }

    public function codigoExiste(string $codigo, ?int $excluirId = null): bool
    {
        if ($codigo === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS total FROM productos WHERE codigo = :codigo';
        $params = ['codigo' => $codigo];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Registra un movimiento de inventario. NUNCA escribe stock_actual
     * directamente: el trigger trg_movimientos_inventario_after_insert es la
     * unica fuente que lo actualiza (ver docs/ARCHITECTURE.md, decision #1).
     */
    public function registrarMovimiento(int $productoId, string $tipo, int $cantidad, ?string $motivo, int $usuarioId, ?string $referenciaTipo = null, ?int $referenciaId = null, ?float $costoUnitario = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO movimientos_inventario (producto_id, tipo, cantidad, costo_unitario, motivo, referencia_tipo, referencia_id, usuario_id, fecha)
             VALUES (:producto_id, :tipo, :cantidad, :costo_unitario, :motivo, :referencia_tipo, :referencia_id, :usuario_id, NOW())'
        );
        $stmt->execute([
            'producto_id' => $productoId, 'tipo' => $tipo, 'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario, 'motivo' => $motivo,
            'referencia_tipo' => $referenciaTipo, 'referencia_id' => $referenciaId, 'usuario_id' => $usuarioId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function siguienteCodigo(): string
    {
        $stmt = $this->db->query(
            "SELECT IFNULL(MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)), 0) + 1 AS siguiente
               FROM productos WHERE codigo REGEXP '^PROD-[0-9]+$'"
        );
        $siguiente = (int) $stmt->fetch()['siguiente'];
        return 'PROD-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }
}
