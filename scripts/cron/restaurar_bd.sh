#!/bin/bash
# =============================================================================
# Restaura la base de datos desde un archivo generado por backup_bd.sh
# Uso: ./restaurar_bd.sh storage/backups/optica_erp_20260101_030000.sql.gz
# =============================================================================
set -e

DIR_PROYECTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ARCHIVO="$1"

if [ -z "$ARCHIVO" ] || [ ! -f "$ARCHIVO" ]; then
    echo "Uso: $0 <ruta-al-archivo-.sql.gz>"
    exit 1
fi

export $(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' "$DIR_PROYECTO/.env" | xargs -d '\n')

echo "ADVERTENCIA: esto sobrescribira la base de datos '$DB_DATABASE'."
read -p "¿Continuar? (escriba 'si' para confirmar): " CONFIRMACION
if [ "$CONFIRMACION" != "si" ]; then
    echo "Cancelado."
    exit 0
fi

gunzip -c "$ARCHIVO" | mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"

echo "Restauracion completada desde: $ARCHIVO"
