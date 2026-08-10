<?php

namespace App\Core;

/**
 * TOTP (RFC 6238) sobre HOTP (RFC 4226), implementado con hash_hmac nativo
 * de PHP -- sin librerias externas. Compatible con Google Authenticator,
 * Authy, Microsoft Authenticator, etc. (SHA1, 6 digitos, pasos de 30s,
 * exactamente los valores por defecto que esas apps esperan).
 */
class Totp
{
    private const ALFABETO_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PASO_SEGUNDOS = 30;
    private const DIGITOS = 6;

    public static function generarSecreto(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function codigoActual(string $secretoBase32, ?int $tiempo = null): string
    {
        $contador = intdiv($tiempo ?? time(), self::PASO_SEGUNDOS);
        return self::hotp(self::base32Decode($secretoBase32), $contador);
    }

    /** Acepta el paso actual y uno antes/despues para tolerar diferencias de reloj */
    public static function verificar(string $secretoBase32, string $codigo, int $ventana = 1): bool
    {
        $codigo = preg_replace('/\s+/', '', $codigo);
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }
        $tiempo = time();
        for ($i = -$ventana; $i <= $ventana; $i++) {
            $esperado = self::codigoActual($secretoBase32, $tiempo + ($i * self::PASO_SEGUNDOS));
            if (hash_equals($esperado, $codigo)) {
                return true;
            }
        }
        return false;
    }

    public static function otpauthUri(string $secretoBase32, string $cuenta, string $emisor = 'OpticaERP'): string
    {
        $etiqueta = rawurlencode($emisor) . ':' . rawurlencode($cuenta);
        return "otpauth://totp/{$etiqueta}?secret={$secretoBase32}&issuer=" . rawurlencode($emisor)
             . '&algorithm=SHA1&digits=' . self::DIGITOS . '&period=' . self::PASO_SEGUNDOS;
    }

    private static function hotp(string $secretoBinario, int $contador): string
    {
        $contadorBytes = pack('N', 0) . pack('N', $contador); // 8 bytes big-endian
        $hash = hash_hmac('sha1', $contadorBytes, $secretoBinario, true);

        $offset = ord($hash[19]) & 0x0F;
        $binario = ((ord($hash[$offset]) & 0x7F) << 24)
                 | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                 | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                 | (ord($hash[$offset + 3]) & 0xFF);

        $codigo = $binario % (10 ** self::DIGITOS);
        return str_pad((string) $codigo, self::DIGITOS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $datos): string
    {
        $binario = '';
        foreach (str_split($datos) as $char) {
            $binario .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $binario = str_pad($binario, (int) (ceil(strlen($binario) / 5) * 5), '0', STR_PAD_RIGHT);

        $resultado = '';
        foreach (str_split($binario, 5) as $grupo) {
            $resultado .= self::ALFABETO_BASE32[bindec($grupo)];
        }
        return $resultado;
    }

    private static function base32Decode(string $datos): string
    {
        $datos = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $datos));
        $binario = '';
        foreach (str_split($datos) as $char) {
            $pos = strpos(self::ALFABETO_BASE32, $char);
            if ($pos === false) {
                continue;
            }
            $binario .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binario, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }
}
