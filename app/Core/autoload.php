<?php
/**
 * Autoloader PSR-4 minimo para el namespace App\.
 *
 * Se incluye para que el nucleo de la aplicacion funcione sin depender de
 * `composer install` (util en hosting compartido donde a veces Composer no
 * esta disponible por SSH). Si vendor/autoload.php existe (tras instalar
 * dependencias opcionales como PHPMailer o Dompdf), este autoloader convive
 * con el de Composer sin conflicto: cada uno resuelve su propio namespace.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
