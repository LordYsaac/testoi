<?php

namespace App\Core;

class Csv
{
    /**
     * Envia un archivo CSV al navegador y termina la ejecucion.
     * $filas es un array de arrays asociativos; las claves del primer
     * elemento se usan como encabezado si no se pasa $encabezados.
     */
    public static function descargar(string $nombreArchivo, array $filas, ?array $encabezados = null): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel detecte UTF-8 correctamente

        if (!empty($filas)) {
            fputcsv($salida, $encabezados ?? array_keys($filas[0]));
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
        } elseif ($encabezados) {
            fputcsv($salida, $encabezados);
        }

        fclose($salida);
        exit;
    }
}
