<?php
/**
 * Sistema de migraciones minimalista.
 *
 * La instalacion inicial usa database/schema.sql + schema_logic.sql + seed.sql
 * (un esquema completo de una sola vez). Este script es para CAMBIOS
 * posteriores: cada archivo .sql dentro de database/migrations/ se aplica
 * una sola vez, en orden alfabetico, y queda registrado en la tabla
 * `migraciones` para no volver a aplicarse.
 *
 * Uso:
 *   php database/migrate.php            # aplica las migraciones pendientes
 *   php database/migrate.php --estado   # solo muestra cuales faltan
 *
 * Convencion de nombres sugerida: 0001_agrega_tabla_x.sql, 0002_agrega_columna_y.sql...
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/autoload.php';

use App\Core\Database;

$soloEstado = in_array('--estado', $argv, true);
$db = Database::getInstance();

$db->exec(
    'CREATE TABLE IF NOT EXISTS migraciones (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        archivo VARCHAR(255) NOT NULL UNIQUE,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$aplicadas = array_column($db->query('SELECT archivo FROM migraciones')->fetchAll(), 'archivo');

$directorio = __DIR__ . '/migrations';
$archivos = glob($directorio . '/*.sql') ?: [];
sort($archivos);

$pendientes = array_filter($archivos, static fn (string $f): bool => !in_array(basename($f), $aplicadas, true));

if (empty($pendientes)) {
    echo "No hay migraciones pendientes (" . count($aplicadas) . " ya aplicadas).\n";
    exit(0);
}

echo count($pendientes) . " migracion(es) pendiente(s):\n";
foreach ($pendientes as $archivo) {
    echo '  - ' . basename($archivo) . "\n";
}

if ($soloEstado) {
    exit(0);
}

foreach ($pendientes as $archivo) {
    $nombre = basename($archivo);
    echo "Aplicando {$nombre}... ";
    try {
        $sql = file_get_contents($archivo);
        $db->exec($sql);
        $stmt = $db->prepare('INSERT INTO migraciones (archivo) VALUES (:archivo)');
        $stmt->execute(['archivo' => $nombre]);
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "FALLO\n";
        fwrite(STDERR, "Error en {$nombre}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Listo.\n";
