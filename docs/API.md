# API REST v1

API HTTP/JSON para integraciones externas (apps móviles, sitios web,
sistemas de terceros). Independiente de las sesiones de navegador del
panel administrativo: se autentica por **API key**, no por cookie.

> **Estado actual:** un módulo (Clientes) implementado como ejemplo
> completo y funcional del patrón a seguir. El resto de módulos se agregan
> con el mismo patrón — ver "Cómo agregar un nuevo endpoint" al final.

## Autenticación

Toda petición debe incluir la cabecera:

```
X-API-Key: <su-llave>
```

Las llaves válidas se configuran en `.env`:

```
API_KEYS=llave-para-integracion-1,llave-para-integracion-2
```

(`config/api.php` las lee de ahí; sin `.env`, usa una llave de desarrollo
que **debe cambiarse antes de producción**.)

Sin llave o con una llave inválida, todo endpoint responde:

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{"error": "API key invalida o ausente. Envie la cabecera X-API-Key."}
```

## Formato de respuesta

Listas: `{"data": [...], "meta": {"pagina":1, "por_pagina":20, "total":N, "total_paginas":N}}`
Un registro: `{"data": {...}}`
Error: `{"error": "mensaje"}`

## Endpoints disponibles

### `GET /api/v1/clientes`

Lista clientes activos, paginada.

**Parámetros de query:** `q` (búsqueda por nombre/código/cédula/teléfono), `pagina`, `por_pagina` (máx. 100).

```bash
curl -H "X-API-Key: SU_LLAVE" "https://sudominio.com/api/v1/clientes?q=Perez&pagina=1"
```

### `GET /api/v1/clientes/{id}`

Un cliente por ID. `404` si no existe.

```bash
curl -H "X-API-Key: SU_LLAVE" "https://sudominio.com/api/v1/clientes/12"
```

### `POST /api/v1/clientes`

Crea un cliente. Body `application/x-www-form-urlencoded` o JSON con
`Content-Type: application/json` (el `Request` del framework lee
`$_POST`; para JSON puro, decodifique `php://input` — ver nota abajo).

**Campos:** `nombres` y `apellidos` obligatorios; `telefono`, `email`, `cedula_pasaporte` opcionales.

```bash
curl -X POST -H "X-API-Key: SU_LLAVE" \
  -d "nombres=Maria&apellidos=Lopez&telefono=809-555-1234" \
  https://sudominio.com/api/v1/clientes
```

Respuesta `201 Created` con el registro creado, o `409 Conflict` si la
cédula ya existe, o `422 Unprocessable Entity` si faltan campos
obligatorios.

## Cómo agregar un nuevo endpoint (ej. Productos)

1. Cree `app/Controllers/Api/ProductosApiController.php` extendiendo
   `App\Core\ApiController` (mismo patrón que `ClientesApiController`).
2. Reutilice el modelo existente (`App\Models\Producto` en Fase 2) — los
   controladores de API no deben repetir SQL, solo llamar al modelo.
3. Registre las rutas en `routes/api.php`:
   ```php
   $router->get('api/v1/productos', [ProductosApiController::class, 'index'], [ApiKeyMiddleware::class]);
   ```
4. Documente el endpoint en este archivo.

## Nota sobre payloads JSON puros

El `Request::input()` actual lee `$_POST`/`$_GET`, que PHP puebla
automáticamente para `application/x-www-form-urlencoded` y
`multipart/form-data`, pero **no** para `application/json`. Si su
integración necesita enviar JSON puro, la forma más simple de extenderlo
sin tocar los controladores existentes es añadir, al inicio de
`routes/api.php`, algo como:

```php
if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?? [];
}
```

## Seguridad de la API

- Cada llave debe ser larga y aleatoria: `php -r "echo bin2hex(random_bytes(32));"`
- Use siempre HTTPS en producción — la llave viaja en la cabecera en texto plano dentro del canal TLS.
- Revoque/rote llaves quitándolas de `API_KEYS` en `.env`.
- Los endpoints de escritura (`POST`) validan y sanitizan igual que el panel web (mismos `Model`, mismas reglas de negocio).
