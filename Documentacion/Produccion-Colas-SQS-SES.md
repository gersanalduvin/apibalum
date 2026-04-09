# Guía de Producción: Colas SQS y SES (Laravel)

Este documento resume la configuración recomendada para ejecutar colas con **Amazon SQS** y envío de correos con **Amazon SES** en producción, incluyendo manejo de SSL/CA bundle y la operación de workers.

## Variables de entorno (.env)

Usa estos valores base en producción. Ajusta los marcados con `<>`.

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=info

QUEUE_CONNECTION=sqs

# SQS
SQS_PREFIX=https://sqs.us-east-2.amazonaws.com/<tu-account-id>
SQS_QUEUE=emails
SQS_SUFFIX=

# AWS
AWS_ACCESS_KEY_ID=<tu-access-key-id>
AWS_SECRET_ACCESS_KEY=<tu-secret-access-key>
AWS_DEFAULT_REGION=us-east-2

# SES
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=notification@gnube.app
MAIL_FROM_NAME="${APP_NAME}"

# SSL (ver secciones más abajo)
AWS_VERIFY_SSL=true
# Linux (si tu build requiere ruta explícita):
# AWS_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt
# Windows Server (si tu build requiere ruta explícita):
# AWS_CA_BUNDLE=C:/php/extras/ssl/cacert.pem
```

Notas:
- Los jobs de email usan `env('QUEUE_EMAIL', 'emails')`. Si deseas personalizarlo, añade `QUEUE_EMAIL=emails` en `.env`.
- Para colas FIFO usa `SQS_QUEUE=emails.fifo` y considera `MessageGroupId`/`DeduplicationId` si tu caso lo requiere.

## SSL y CA bundle

### Linux (recomendado)
- Instala certificados del sistema: `sudo apt-get install -y ca-certificates`.
- PHP suele usar el store del sistema; no definas `AWS_CA_BUNDLE` salvo que tu build lo requiera.
- Si tu build necesita ruta explícita:
  - `.env`: `AWS_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt`, `AWS_VERIFY_SSL=true`.
  - Verifica con:
    - `php -r "print_r(curl_version());"` (debería mostrar `ssl_version` tipo OpenSSL)
    - `php -r "echo ini_get('curl.cainfo'), PHP_EOL; echo ini_get('openssl.cafile'), PHP_EOL;"`

### Windows Server
- Descarga `cacert.pem` oficial: https://curl.se/ca/cacert.pem
- Ubícalo en: `C:\php\extras\ssl\cacert.pem` (o tu ruta preferida).
- Edita `php.ini` (CLI y SAPI en uso) y define:
  - `curl.cainfo = C:\php\extras\ssl\cacert.pem`
  - `openssl.cafile = C:\php\extras\ssl\cacert.pem`
- `.env`: `AWS_VERIFY_SSL=true` y, si tu build requiere ruta explícita: `AWS_CA_BUNDLE=C:/php/extras/ssl/cacert.pem`.

### Evitar desactivar SSL
- En producción siempre usa `AWS_VERIFY_SSL=true`.
- Solo en desarrollo local, si aparece `cURL error 60`, puedes establecer temporalmente `AWS_VERIFY_SSL=false` hasta solucionar el CA bundle.
- En este proyecto, `config/aws.php` y `config/queue.php` ya soportan `AWS_VERIFY_SSL` y usan `AWS_CA_BUNDLE` cuando la verificación está activa.

## Certificados de dominio vs CA

- Los certificados de tu dominio (servidor “leaf”, p. ej. `*.tudominio.com`) no son autoridades certificadoras y no sirven para validar los endpoints de AWS (SQS, SES, S3, etc.).
- Debes usar un bundle de raíces de confianza (CA bundle) que incluya las CAs que firman los certificados de AWS (p. ej., "Amazon Root CA 1" de Amazon Trust Services, DigiCert, etc.).
- No apuntes `AWS_CA_BUNDLE` ni `curl.cainfo`/`openssl.cafile` a un certificado de servidor de tu dominio; debe ser un archivo de CA bundle válido.

### Redes con inspección TLS (proxy corporativo / CA interna)
- Si tu organización intercepta TLS y re-firma el tráfico con una **CA interna**, debes confiar esa CA interna para que la verificación funcione.
- Linux: coloca el certificado raíz/intermedio de la CA interna en `/usr/local/share/ca-certificates/` (formato `.crt`/PEM) y ejecuta `sudo update-ca-certificates`. PHP/cURL usarán el store del sistema.
- Windows Server: importa la CA interna en `Trusted Root Certification Authorities` del sistema (usando `mmc` → snap-in Certificates → Local Computer). cURL/PHP compilados con Schannel usarán este store.
- Proyecto: si tu build requiere ruta explícita, puedes crear un `AWS_CA_BUNDLE` que **concatene** la CA interna + el bundle oficial (orden no crítico para la mayoría de clientes). Define `AWS_CA_BUNDLE` en `.env`.
- Nota Schannel: en Windows, `--cacert`/`curl.cainfo` pueden ser ignorados si cURL/PHP usan Schannel; en ese caso, instala la raíz en el store de Windows.

**Resumen**
- Producción: `AWS_VERIFY_SSL=true` y usar el store del sistema siempre que sea posible.
- Si se usa `AWS_CA_BUNDLE`, asegúrate de que sea un **bundle de CAs** (no tu certificado de dominio) y que incluya la CA que firma los endpoints de AWS o la CA interna de tu proxy.


## Workers en producción

### Supervisor (Linux)
Crea `/etc/supervisor/conf.d/laravel-queue.conf`:

```
[program:laravel-queue]
command=/usr/bin/php /var/www/apicmpp/artisan queue:work sqs --queue=emails --sleep=3 --tries=3 --max-time=3600
directory=/var/www/apicmpp
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
```

Carga y arranca:

```
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### Alternativas
- Horizon (Redis) si buscas dashboard y escalado fino.
- Windows Server: NSSM o Task Scheduler para mantener `php artisan queue:work sqs --queue=emails` en segundo plano.

## Pruebas y verificación

- Limpia caché de configuración:
  - `php artisan optimize:clear`
- Confirmar SSL/cURL:
  - `php -r "print_r(curl_version());"`
  - `php -r "echo 'curl.cainfo=' . ini_get('curl.cainfo') . PHP_EOL; echo 'openssl.cafile=' . ini_get('openssl.cafile') . PHP_EOL;"`
- Probar dispatch y consumo:
  - `php artisan app:dispatch-test-email you@yourdomain.com --type=simple --subject="Prueba Prod" --body="Hola SQS!"`
  - `php artisan queue:work --queue=emails --tries=1 --stop-when-empty`
- Jobs fallidos:
  - `php artisan queue:failed`

## Buenas prácticas

- Mantén credenciales fuera del repositorio; usa variables de entorno/secretos del orquestador.
- IAM con privilegios mínimos (SQS/SES solo lo necesario).
- Monitorea logs y métricas; configura alertas para `queue:failed`.
- Verifica que el dominio/remitente estén aprobados en SES y fuera del sandbox si envías a direcciones no verificadas.
- Evita usar rutas de Windows en servidores Linux.

## FIFO (si aplica)

- Usa `emails.fifo`.
- Considera `MessageGroupId` para orden y `MessageDeduplicationId` si tu patrón de publicación lo requiere.
- Laravel crea mensajes SQS válidos para FIFO cuando el nombre termina en `.fifo`, pero revisa tu lógica si necesitas grupos específicos.

## Troubleshooting

- `cURL error 60` (unable to get local issuer certificate):
  - Asegura `AWS_VERIFY_SSL=true` y que `cacert.pem` sea el actualizado.
  - Linux: instala `ca-certificates`; usa `/etc/ssl/certs/ca-certificates.crt` si tu build necesita ruta.
  - Windows: define `curl.cainfo` y `openssl.cafile` en `php.ini` y usa una ruta válida en `.env` si es necesario.
  - Verifica que no haya inspección TLS/antivirus corporativo inyectando certificados.
- `AccessDenied`/`The AWS Access Key Id you provided does not exist`: revisa IAM credenciales y políticas.
- `Email address not verified`: valida direcciones/domino en SES; fuera de sandbox para envíos generales.

## Checklist final

- `APP_ENV=production` y `APP_DEBUG=false`.
- `QUEUE_CONNECTION=sqs` y `SQS_PREFIX/SQS_QUEUE` correctos.
- `AWS_ACCESS_KEY_ID/AWS_SECRET_ACCESS_KEY/AWS_DEFAULT_REGION` correctos.
- `MAIL_MAILER=ses` y remitente verificado.
- `AWS_VERIFY_SSL=true` y CA bundle correctamente configurado según el sistema.
- Worker corriendo bajo Supervisor/NSSM.
- Pruebas de dispatch/consumo completadas y sin jobs fallidos.