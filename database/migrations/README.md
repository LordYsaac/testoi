# Migraciones

La instalación inicial de este proyecto **no** usa esta carpeta: se hace
cargando `database/schema.sql` → `database/schema_logic.sql` →
`database/seed.sql` una sola vez (ver `docs/INSTALL.md`).

Esta carpeta es para **cambios futuros** al esquema, una vez el sistema ya
está en producción con datos reales. Agregue aquí archivos `.sql`
numerados secuencialmente:

```
0001_agrega_tabla_promociones_temporada.sql
0002_agrega_columna_clientes_nfc.sql
```

Y ejecute:

```bash
php database/migrate.php --estado   # ver que falta por aplicar
php database/migrate.php            # aplicar
```

Cada archivo se aplica **una sola vez** (queda registrado en la tabla
`migraciones`) y en orden alfabético — de ahí la numeración con ceros a
la izquierda.
