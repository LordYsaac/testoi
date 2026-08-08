<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * El expediente clinico es un AGREGADO: un encabezado (expedientes_clinicos)
 * con hasta 7 secciones 1:1 (agudeza visual, refraccion, DP, queratometria,
 * tonometria, biomicroscopia, fondo de ojo) y dos listas 1:N (diagnosticos,
 * tratamientos). Todo se crea/actualiza en una unica transaccion para que
 * el expediente nunca quede a medias.
 */
class ExpedienteClinico extends Model
{
    protected string $table = 'expedientes_clinicos';

    private const SECCIONES_1A1 = [
        'agudeza_visual'  => ['od_sin_correccion', 'od_con_correccion', 'oi_sin_correccion', 'oi_con_correccion', 'od_vision_cercana', 'oi_vision_cercana', 'od_vision_lejana', 'oi_vision_lejana'],
        'refraccion'      => ['od_esfera', 'od_cilindro', 'od_eje', 'od_adicion', 'od_prisma', 'oi_esfera', 'oi_cilindro', 'oi_eje', 'oi_adicion', 'oi_prisma'],
        'distancia_pupilar' => ['dp_binocular', 'dp_od', 'dp_oi'],
        'queratometria'   => ['od_k1', 'od_k2', 'od_eje', 'oi_k1', 'oi_k2', 'oi_eje'],
        'tonometria'      => ['od_valor', 'oi_valor', 'metodo', 'hora'],
        'biomicroscopia'  => ['od_parpados', 'od_conjuntiva', 'od_cornea', 'od_camara_anterior', 'od_iris', 'od_cristalino', 'oi_parpados', 'oi_conjuntiva', 'oi_cornea', 'oi_camara_anterior', 'oi_iris', 'oi_cristalino', 'observaciones'],
        'fondo_ojo'       => ['od_papila', 'od_retina', 'od_macula', 'od_vasos', 'od_periferia', 'oi_papila', 'oi_retina', 'oi_macula', 'oi_vasos', 'oi_periferia', 'observaciones'],
    ];

    public function crearCompleto(array $encabezado, array $secciones, array $diagnosticos, array $tratamientos): int
    {
        return Database::transaction(function () use ($encabezado, $secciones, $diagnosticos, $tratamientos) {
            $expedienteId = $this->create($encabezado);

            foreach (self::SECCIONES_1A1 as $tabla => $columnas) {
                $datos = $this->filtrarNoVacio($secciones[$tabla] ?? [], $columnas);
                if (empty($datos)) {
                    continue;
                }
                $datos['expediente_id'] = $expedienteId;
                $this->insertarEn($tabla, $datos);
            }

            foreach (array_filter($diagnosticos) as $diagnostico) {
                $this->insertarEn('diagnosticos', ['expediente_id' => $expedienteId, 'diagnostico' => $diagnostico]);
            }
            foreach (array_filter($tratamientos) as $tratamiento) {
                $this->insertarEn('tratamientos', ['expediente_id' => $expedienteId, 'tratamiento' => $tratamiento]);
            }

            return $expedienteId;
        });
    }

    public function actualizarCompleto(int $id, array $encabezado, array $secciones, array $diagnosticos, array $tratamientos): void
    {
        Database::transaction(function () use ($id, $encabezado, $secciones, $diagnosticos, $tratamientos) {
            $this->update($id, $encabezado);

            foreach (self::SECCIONES_1A1 as $tabla => $columnas) {
                $datos = $this->filtrarNoVacio($secciones[$tabla] ?? [], $columnas);

                $existe = $this->db->prepare("SELECT id FROM {$tabla} WHERE expediente_id = :eid");
                $existe->execute(['eid' => $id]);
                $fila = $existe->fetch();

                if (empty($datos)) {
                    continue;
                }

                if ($fila) {
                    $set = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_keys($datos)));
                    $stmt = $this->db->prepare("UPDATE {$tabla} SET {$set} WHERE expediente_id = :expediente_id");
                    $datos['expediente_id'] = $id;
                    $stmt->execute($datos);
                } else {
                    $datos['expediente_id'] = $id;
                    $this->insertarEn($tabla, $datos);
                }
            }

            $this->db->prepare('DELETE FROM diagnosticos WHERE expediente_id = :id')->execute(['id' => $id]);
            foreach (array_filter($diagnosticos) as $diagnostico) {
                $this->insertarEn('diagnosticos', ['expediente_id' => $id, 'diagnostico' => $diagnostico]);
            }

            $this->db->prepare('DELETE FROM tratamientos WHERE expediente_id = :id')->execute(['id' => $id]);
            foreach (array_filter($tratamientos) as $tratamiento) {
                $this->insertarEn('tratamientos', ['expediente_id' => $id, 'tratamiento' => $tratamiento]);
            }
        });
    }

    public function obtenerCompleto(int $id): array|false
    {
        $sql = 'SELECT e.*,
                       cl.nombres AS cliente_nombres, cl.apellidos AS cliente_apellidos, cl.codigo_cliente,
                       fn_edad(cl.fecha_nacimiento) AS cliente_edad, cl.sexo AS cliente_sexo,
                       CONCAT(u.nombre, " ", u.apellido) AS doctor_nombre,
                       av.od_sin_correccion, av.od_con_correccion, av.oi_sin_correccion, av.oi_con_correccion,
                       av.od_vision_cercana, av.oi_vision_cercana, av.od_vision_lejana, av.oi_vision_lejana,
                       r.od_esfera, r.od_cilindro, r.od_eje AS r_od_eje, r.od_adicion, r.od_prisma,
                       r.oi_esfera, r.oi_cilindro, r.oi_eje AS r_oi_eje, r.oi_adicion, r.oi_prisma,
                       dp.dp_binocular, dp.dp_od, dp.dp_oi,
                       k.od_k1, k.od_k2, k.od_eje AS k_od_eje, k.oi_k1, k.oi_k2, k.oi_eje AS k_oi_eje,
                       t.od_valor AS tono_od, t.oi_valor AS tono_oi, t.metodo AS tono_metodo, t.hora AS tono_hora,
                       b.od_parpados, b.od_conjuntiva, b.od_cornea, b.od_camara_anterior, b.od_iris, b.od_cristalino,
                       b.oi_parpados, b.oi_conjuntiva, b.oi_cornea, b.oi_camara_anterior, b.oi_iris, b.oi_cristalino,
                       b.observaciones AS biomicroscopia_obs,
                       f.od_papila, f.od_retina, f.od_macula, f.od_vasos, f.od_periferia,
                       f.oi_papila, f.oi_retina, f.oi_macula, f.oi_vasos, f.oi_periferia,
                       f.observaciones AS fondo_ojo_obs
                  FROM expedientes_clinicos e
                  JOIN clientes cl ON cl.id = e.cliente_id
                  LEFT JOIN usuarios u ON u.id = e.doctor_id
                  LEFT JOIN agudeza_visual av ON av.expediente_id = e.id
                  LEFT JOIN refraccion r ON r.expediente_id = e.id
                  LEFT JOIN distancia_pupilar dp ON dp.expediente_id = e.id
                  LEFT JOIN queratometria k ON k.expediente_id = e.id
                  LEFT JOIN tonometria t ON t.expediente_id = e.id
                  LEFT JOIN biomicroscopia b ON b.expediente_id = e.id
                  LEFT JOIN fondo_ojo f ON f.expediente_id = e.id
                 WHERE e.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $expediente = $stmt->fetch();

        if (!$expediente) {
            return false;
        }

        $diag = $this->db->prepare('SELECT id, diagnostico, cie10 FROM diagnosticos WHERE expediente_id = :id ORDER BY id');
        $diag->execute(['id' => $id]);
        $expediente['diagnosticos'] = $diag->fetchAll();

        $trat = $this->db->prepare('SELECT id, tratamiento FROM tratamientos WHERE expediente_id = :id ORDER BY id');
        $trat->execute(['id' => $id]);
        $expediente['tratamientos'] = $trat->fetchAll();

        $adj = $this->db->prepare('SELECT id, tipo, nombre_original, ruta_archivo, descripcion, created_at FROM expediente_adjuntos WHERE expediente_id = :id ORDER BY created_at DESC');
        $adj->execute(['id' => $id]);
        $expediente['adjuntos'] = $adj->fetchAll();

        return $expediente;
    }

    /** Historial cronologico de un paciente (para la pestana "Historial medico" del cliente) */
    public function porCliente(int $clienteId): array
    {
        $sql = 'SELECT e.id, e.fecha, e.motivo_consulta, e.estado,
                       CONCAT(u.nombre, " ", u.apellido) AS doctor_nombre,
                       (SELECT GROUP_CONCAT(diagnostico SEPARATOR "; ") FROM diagnosticos WHERE expediente_id = e.id) AS diagnosticos
                  FROM expedientes_clinicos e
                  LEFT JOIN usuarios u ON u.id = e.doctor_id
                 WHERE e.cliente_id = :cid
                 ORDER BY e.fecha DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function agregarAdjunto(int $expedienteId, string $tipo, string $nombreOriginal, string $ruta, ?string $descripcion, int $usuarioId): int
    {
        return $this->insertarEn('expediente_adjuntos', [
            'expediente_id'   => $expedienteId,
            'tipo'            => $tipo,
            'nombre_original' => $nombreOriginal,
            'ruta_archivo'    => $ruta,
            'descripcion'     => $descripcion,
            'subido_por'      => $usuarioId,
        ]);
    }

    private function insertarEn(string $tabla, array $datos): int
    {
        $columnas = array_keys($datos);
        $placeholders = array_map(static fn ($c) => ":{$c}", $columnas);
        $stmt = $this->db->prepare(
            "INSERT INTO {$tabla} (" . implode(',', $columnas) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($datos);
        return (int) $this->db->lastInsertId();
    }

    private function filtrarNoVacio(array $datos, array $columnasPermitidas): array
    {
        $filtrado = array_intersect_key($datos, array_flip($columnasPermitidas));
        return array_filter($filtrado, static fn ($v) => $v !== null && $v !== '');
    }
}
