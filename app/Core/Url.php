<?php

namespace App\Core;

/**
 * Genera URLs relativas al punto de montaje real de la app, para que todo
 * funcione igual si el sistema vive en la raiz del dominio (https://midominio.com/)
 * o en un subdirectorio (https://midominio.com/optica/), comun en hosting compartido.
 */
class Url
{
    public static function base(): string
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim(str_replace('\\', '/', $scriptDir), '/');
    }

    public static function to(string $path = ''): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::to('assets/' . ltrim($path, '/'));
    }

    public static function actual(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return trim($uri, '/');
    }

    public static function esActiva(string $path): bool
    {
        $actual = self::actual();
        $base = trim(self::base(), '/');
        if ($base !== '' && str_starts_with($actual, $base)) {
            $actual = trim(substr($actual, strlen($base)), '/');
        }
        return $actual === trim($path, '/') || str_starts_with($actual, trim($path, '/') . '/');
    }
}
