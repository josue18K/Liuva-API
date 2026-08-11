# Liuva API REST

Base local: `http://127.0.0.1:8000/api`

Todas las solicitudes y respuestas usan JSON. Las rutas protegidas requieren
`Authorization: Bearer {token}` y `Accept: application/json`.

## Flujo del vendedor

1. `POST /register` crea una cuenta pendiente.
2. `POST /login` devuelve el token Sanctum.
3. `GET /me` devuelve perfil, estado y sede.
4. `POST /account/activate` consume una licencia disponible.
5. `POST /cash-registers/open` abre la caja con denominaciones.
6. `GET /sales/search-products?sede_id={id}&q={texto}` busca o resuelve un código escaneado.
7. `POST /sales` registra la venta y descuenta stock.
8. `POST /sales/{sale}/generate-receipt` genera el PNG y enlace de WhatsApp.
9. `POST /cash-registers/close` realiza el arqueo.

## Autenticación y cuenta

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/register` | Público |
| POST | `/login` | Público |
| GET | `/me` | Autenticado, incluso pendiente |
| POST | `/logout` | Autenticado |
| POST | `/account/activate` | Autenticado y pendiente |

## Administración

Todas las rutas siguientes requieren cuenta activa y rol `admin`.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/dashboard` | Comprobación del panel |
| GET, POST | `/admin/licenses` | Listar o generar licencias |
| PUT | `/admin/licenses/{license}/status` | Bloquear o desbloquear licencia |
| GET, POST | `/admin/sellers` | Listar o crear vendedores pendientes |
| GET, PUT | `/admin/sellers/{seller}` | Consultar o actualizar vendedor |
| GET, POST | `/admin/sedes` | Listar o crear sedes |
| GET, PUT | `/admin/sedes/{sede}` | Consultar o actualizar sede |
| GET, POST | `/admin/categories` | Catálogo paginado de categorías |
| GET, PUT | `/admin/categories/{category}` | Consultar o actualizar categoría |
| GET, POST | `/admin/products` | Buscar/listar o crear productos |
| GET, PUT | `/admin/products/{product}` | Consultar o actualizar producto |
| GET, POST | `/admin/inventory-movements` | Historial o nuevo movimiento de stock |
| GET | `/admin/inventory-reports/sede/{sede}` | Reporte de texto para WhatsApp |
| GET | `/admin/activity-logs` | Bitácora paginada y filtrable |
| GET | `/admin/activity-logs/{activityLog}` | Detalle de bitácora |
| GET, POST | `/admin/settings` | Consultar o guardar configuraciones |
| GET | `/admin/settings/{key}` | Consultar una configuración |

Las rutas `/admin/inventory-adjustments` se conservan temporalmente para
compatibilidad con la primera versión. Las aplicaciones nuevas deben usar
`/admin/inventory-movements`.

## Ventas, caja y comprobantes

Requieren cuenta activa. Los vendedores solo ven sus propios datos y operan en
su sede; el administrador puede consultar todas las sedes.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/sales/search-products` | Buscar por nombre, código interno o barras |
| GET, POST | `/sales` | Historial paginado o registrar venta |
| GET | `/sales/{sale}` | Detalle autorizado de venta |
| POST | `/sales/{sale}/generate-receipt` | Generar comprobante PNG |
| GET | `/cash-registers` | Historial paginado de caja |
| GET | `/cash-registers/{cashRegister}` | Detalle y denominaciones |
| POST | `/cash-registers/open` | Apertura |
| POST | `/cash-registers/close` | Cierre y arqueo |
| GET | `/public/receipts/{uuid}` | Imagen pública no enumerable |

## Códigos de seguridad

| Código | Significado |
|---|---|
| `UNAUTHENTICATED` | Falta un token válido |
| `ACCOUNT_PENDING` | Falta activar la cuenta |
| `ACCOUNT_DISABLED` | Administrador deshabilitó la cuenta |
| `INSUFFICIENT_ROLE` | El rol no permite la operación |
| `RESOURCE_NOT_FOUND` | Recurso inexistente o no visible |

## Pruebas

Ejecutar desde la raíz:

```powershell
php artisan test
```

La suite usa SQLite en memoria, reconstruye todo el esquema y no altera MySQL.
