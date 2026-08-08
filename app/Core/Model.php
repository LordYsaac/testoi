<?php

namespace App\Core;

use PDO;

/**
 * Modelo base. Los modelos concretos (Cliente, Producto, etc.) extienden
 * esta clase y anaden sus propias consultas especificas del dominio.
 * Todas las consultas usan prepared statements con parametros nombrados.
 */
abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return $this->db->query($sql)->fetchAll();
    }

    public function where(string $column, $value, string $operator = '='): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} :value";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = :pk_id";

        $data['pk_id'] = $id;
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** Desactivacion logica (estado = 'inactivo'). Preferido sobre delete() para datos con historial. */
    public function desactivar(int $id): bool
    {
        return $this->update($id, ['estado' => 'inactivo']);
    }

    public function contar(string $whereSql = '1', array $params = []): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM {$this->table} WHERE {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }
}
