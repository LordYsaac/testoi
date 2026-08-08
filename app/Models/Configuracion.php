<?php

namespace App\Models;

use App\Core\Database;

class Configuracion
{
    private static ?array $cache = null;

    public static function empresa(): array
    {
        if (self::$cache === null) {
            $stmt = Database::getInstance()->query('SELECT * FROM configuracion_empresa WHERE id = 1');
            self::$cache = $stmt->fetch() ?: [];
        }
        return self::$cache;
    }
}
