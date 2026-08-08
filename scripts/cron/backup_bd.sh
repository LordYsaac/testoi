#!/bin/bash
# =============================================================================
# Respaldo diario de la base de datos. Programar en cPanel/LiteSpeed Cron Jobs:
#   0 3 * * *  /ruta/completa/al/proyecto/scripts/cron/backup_bd.sh
#
# Requiere que las credenciales existan en .env (mismo formato que usa la app).
# Conserva los ultimos 14 respaldos y borra los mas antiguos automaticamente.
# =============================================================================
set -e

DIR_PROYECTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DIR_BACKUPS="$DIR_PROYECTO/storage/backups"
FECHA=$(date +%Y%m%d_%H%M%S)
RETENER=14

mkdir -p "$DIR_BACKUPS"

# Leer credenciales del .env sin depender de PHP
export $(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' "$DIR_PROYECTO/.env" | xargs -d '\n')

ARCHIVO="$DIR_BACKUPS/optica_erp_${FECHA}.sql.gz"

mysqldump --single-transaction --routines --triggers --events \
  -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "$ARCHIVO"

TAMANO=$(stat -c%s "$ARCHIVO" 2>/dev/null || stat -f%z "$ARCHIVO")

mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e \
  "INSERT INTO backups_log (nombre_archivo, tamano_bytes, tipo, estado, created_at) VALUES ('$(basename "$ARCHIVO")', $TAMANO, 'automatico', 'exitoso', NOW());"

# Conservar solo los ultimos $RETENER respaldos
ls -1t "$DIR_BACKUPS"/optica_erp_*.sql.gz | tail -n +$((RETENER + 1)) | xargs -r rm --

echo "Respaldo creado: $ARCHIVO ($TAMANO bytes)"
