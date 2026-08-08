<?php

namespace App\Core;

/**
 * Generacion de codigos QR para validar recetas impresas.
 *
 * Por defecto usa un servicio publico de imagenes QR (sin dependencias,
 * funciona en cualquier hosting). Si se requiere generarlos localmente
 * (offline o por politica de privacidad de datos), instalar via Composer
 * una libreria como "endroid/qr-code" y reemplazar el cuerpo de
 * urlImagen() por la generacion local; el resto del sistema (vistas de
 * impresion, validacion publica) no necesita cambios porque solo consume
 * esta clase.
 */
class QrCode
{
    public static function urlImagen(string $contenido, int $tamano = 200): string
    {
        $contenido = urlencode($contenido);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$tamano}x{$tamano}&margin=5&data={$contenido}";
    }

    /** URL publica que el QR de una receta debe apuntar, para que cualquiera pueda validarla */
    public static function urlValidacionReceta(string $codigoValidacion): string
    {
        $base = getenv('APP_URL') ?: (Url::base());
        return rtrim((string) $base, '/') . '/validar-receta/' . $codigoValidacion;
    }
}
