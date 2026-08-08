<?php
/**
 * Funciones helper globales de uso frecuente en las vistas.
 * Se cargan una vez desde public/index.php.
 */

declare(strict_types=1);

if (!function_exists('e')) {
    /** Escapa HTML para prevenir XSS. Usar SIEMPRE al imprimir datos del usuario. */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('moneda')) {
    function moneda(float|string|null $valor, string $simbolo = 'RD$'): string
    {
        return $simbolo . ' ' . number_format((float) $valor, 2);
    }
}

if (!function_exists('fecha_larga')) {
    function fecha_larga(?string $fecha): string
    {
        if (!$fecha) {
            return '';
        }
        $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $ts = strtotime($fecha);
        return date('d', $ts) . ' ' . $meses[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

if (!function_exists('fecha_hora')) {
    function fecha_hora(?string $fecha): string
    {
        return $fecha ? date('d/m/Y h:i A', strtotime($fecha)) : '';
    }
}

if (!function_exists('edad_desde')) {
    function edad_desde(?string $fechaNacimiento): ?int
    {
        if (!$fechaNacimiento) {
            return null;
        }
        return (new DateTime($fechaNacimiento))->diff(new DateTime('now'))->y;
    }
}

if (!function_exists('estado_badge')) {
    /** Devuelve las clases Bootstrap para un badge segun el valor de estado */
    function estado_badge(string $estado): string
    {
        return match ($estado) {
            'activo', 'activa', 'pagada', 'confirmada', 'finalizada', 'exitoso', 'abierta' => 'badge-success',
            'inactivo', 'anulada', 'cancelada', 'fallido', 'vencida' => 'badge-danger',
            'pendiente', 'parcial', 'tentative' => 'badge-warning',
            default => 'badge-neutral',
        };
    }
}

if (!function_exists('slug')) {
    function slug(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
        $texto = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $texto), '-'));
        return $texto;
    }
}

if (!function_exists('generar_codigo_validacion')) {
    function generar_codigo_validacion(): string
    {
        return bin2hex(random_bytes(16));
    }
}

if (!function_exists('subir_archivo')) {
    /**
     * Sube un archivo de $_FILES de forma segura: valida extension/MIME real,
     * genera un nombre aleatorio (evita path traversal y colisiones) y lo
     * mueve a storage/uploads/{carpeta}. Devuelve la ruta relativa o null.
     */
    function subir_archivo(array $file, string $carpeta, array $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp']): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionesPermitidas, true)) {
            return null;
        }

        $mimePermitidos = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'webp' => 'image/webp', 'pdf' => 'application/pdf',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (($mimePermitidos[$extension] ?? null) !== $mimeReal) {
            return null;
        }

        $nombreDestino = bin2hex(random_bytes(16)) . '.' . $extension;
        $carpetaDestino = __DIR__ . '/../../storage/uploads/' . trim($carpeta, '/');
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $rutaCompleta = $carpetaDestino . '/' . $nombreDestino;
        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return null;
        }

        return trim($carpeta, '/') . '/' . $nombreDestino;
    }
}

if (!function_exists('old')) {
    /** Repuebla un campo de formulario tras un error de validacion */
    function old(string $key, mixed $default = ''): string
    {
        $valor = $_SESSION['_old'][$key] ?? $default;
        return e(is_string($valor) ? $valor : (string) $valor);
    }
}
