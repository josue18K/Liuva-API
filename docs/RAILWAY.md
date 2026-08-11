# Despliegue en Railway

## Servicios

Crear un proyecto Railway con dos servicios:

1. Servicio GitHub desde `josue18K/Liuva-API`, rama estable elegida.
2. Servicio MySQL administrado por Railway.

El repositorio contiene `railway.json` y `Dockerfile`; Railway detectará ambos.

## Variables obligatorias

Configurar en el servicio de la API:

```text
APP_NAME=Liuva API
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://dominio-generado.up.railway.app
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Generar `APP_KEY` localmente y copiar únicamente el resultado a Railway:

```powershell
php artisan key:generate --show
```

No subir la clave a GitHub.

## Almacenamiento de comprobantes

Añadir un volumen Railway al servicio API y montarlo en:

```text
/var/www/html/storage/app/public
```

Sin volumen, los PNG generados desaparecerán en cada nuevo despliegue.

## Dominio y comprobación

Generar un dominio público desde Settings > Networking. Actualizar `APP_URL` con
ese dominio y volver a desplegar. El health check esperado es:

```text
GET https://dominio/up
```

Debe responder `200`. Después importar la colección Postman de `docs/postman`,
cambiar `base_url` a `https://dominio/api` y ejecutar el flujo completo.

## Primer administrador

Abrir una shell del servicio API y ejecutar:

```text
php artisan liuva:create-admin
```

El comando solicita correo y contraseña de forma interactiva. No existe ninguna
contraseña administrativa predeterminada en el código.

## Migraciones

Railway ejecuta `php artisan migrate --force` como comando previo al despliegue.
Las migraciones son incrementales e idempotentes; nunca ejecuta `migrate:fresh`.
Apache escucha automáticamente el puerto dinámico proporcionado por Railway.
