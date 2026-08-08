<?php

namespace App\Models;

use App\Core\Model;

class Cliente extends Model
{
    protected string $table = 'clientes';

    public function conDetalle(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM vista_clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function listar(string $busqueda = '', string $estado = '', int $limite = 50, int $pagina = 1): array
    {
        $offset = max(0, ($pagina - 1) * $limite);
        $condiciones = [];
        $params = [];

        if ($busqueda !== '') {
            $condiciones[] = '(c.nombres LIKE :busqueda OR c.apellidos LIKE :busqueda OR c.codigo_cliente LIKE :busqueda OR c.cedula_pasaporte LIKE :busqueda OR c.telefono LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($estado !== '') {
            $condiciones[] = 'c.estado = :estado';
            $params['estado'] = $estado;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT c.id, c.codigo_cliente, c.foto, c.nombres, c.apellidos, c.telefono, c.whatsapp,
                       c.email, c.cedula_pasaporte, c.estado, sm.nombre AS seguro_medico,
                       fn_edad(c.fecha_nacimiento) AS edad
                  FROM clientes c
                  LEFT JOIN seguros_medicos sm ON sm.id = c.seguro_medico_id
                  {$where}
                 ORDER BY c.nombres, c.apellidos
                 LIMIT {$limite} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalListar(string $busqueda = '', string $estado = ''): int
    {
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = '(nombres LIKE :busqueda OR apellidos LIKE :busqueda OR codigo_cliente LIKE :busqueda OR cedula_pasaporte LIKE :busqueda OR telefono LIKE :busqueda)';
            $params['busqueda'] = "%{$busqueda}%";
        }
        if ($estado !== '') {
            $condiciones[] = 'estado = :estado';
            $params['estado'] = $estado;
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM clientes {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    public function frecuentes(int $limite = 10): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vista_clientes_frecuentes LIMIT ' . (int) $limite);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function morosos(): array
    {
        return $this->db->query('SELECT * FROM vista_clientes_morosos ORDER BY dias_vencido DESC')->fetchAll();
    }

    public function obtenerAntecedentes(int $clienteId): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM antecedentes_medicos WHERE cliente_id = :cid');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetch();
    }

    public function guardarAntecedentes(int $clienteId, array $data, int $usuarioId): void
    {
        $existente = $this->obtenerAntecedentes($clienteId);
        $data['updated_by'] = $usuarioId;

        if ($existente) {
            $set = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_keys($data)));
            $stmt = $this->db->prepare("UPDATE antecedentes_medicos SET {$set} WHERE cliente_id = :cliente_id");
            $data['cliente_id'] = $clienteId;
            $stmt->execute($data);
        } else {
            $data['cliente_id'] = $clienteId;
            $columnas = array_keys($data);
            $placeholders = array_map(static fn ($c) => ":{$c}", $columnas);
            $stmt = $this->db->prepare(
                'INSERT INTO antecedentes_medicos (' . implode(',', $columnas) . ') VALUES (' . implode(',', $placeholders) . ')'
            );
            $stmt->execute($data);
        }
    }

    public function cedulaExiste(string $cedula, ?int $excluirId = null): bool
    {
        if ($cedula === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS total FROM clientes WHERE cedula_pasaporte = :cedula';
        $params = ['cedula' => $cedula];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function segurosMedicos(): array
    {
        return $this->db->query("SELECT id, nombre FROM seguros_medicos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }
}
