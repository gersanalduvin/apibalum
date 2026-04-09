# API: Resetear y Enviar Contraseña (Usuarios Administrativos)

## Descripción
Permite a un administrador generar una contraseña temporal de 6 dígitos para un usuario administrativo, actualizarla y enviar los datos de acceso por correo electrónico.

## Base URL
`/api/v1/usuarios/administrativos`

## Endpoint
- **Método**: `POST`
- **Ruta**: `/{id}/reset-password`
- **Autenticación**: `auth:sanctum` (Bearer Token)
- **Permiso requerido**: `usuarios.administrativos.cambiar_password`
- **Middleware**: `check.permissions:usuarios.administrativos.cambiar_password`

## Headers
- `Content-Type: application/json`
- `Authorization: Bearer {token}`

## Parámetros del Request
No requiere cuerpo. El `id` del usuario administrativo se envía en la ruta.

## Comportamiento
- Genera una contraseña temporal aleatoria de **6 dígitos** (incluye ceros a la izquierda).
- Actualiza la contraseña del usuario mediante `UserService::changePasswordAdmin` (hash seguro y registro en historial de cambios).
- Determina el destinatario del correo:
  - Prioriza `correo_notificaciones` si existe y es válido.
  - En caso contrario, utiliza `email` del usuario.
- Contenido del correo incluye:
  - Saludo con nombre y apellido (si disponibles).
  - **Usuario**: correo del usuario (`email`).
  - **URL de acceso**: tomada de `config('app.frontend_url')` (lee `APP_URL_FRONTEND` en `.env`; fallback a `APP_URL` y `http://localhost`).
  - **Contraseña temporal**: la nueva contraseña de 6 dígitos.
  - Nota de seguridad para cambiar la contraseña al iniciar sesión.
  - Aviso: "Este correo es solo de notificaciones; por favor, no responder."
- Encola el envío del correo con `SendEmailJob` (tipo `simple`) para su procesamiento vía SES.

## Respuestas
- **200 OK**
  ```json
  {
    "success": true,
    "data": null,
    "message": "Contraseña reiniciada y correo encolado para envío"
  }
  ```
- **401 Unauthorized**: falta de autenticación.
- **403 Forbidden**: el usuario autenticado no tiene el permiso requerido.
- **404 Not Found**: usuario no encontrado.
- **422 Unprocessable Entity**: usuario sin correo válido para notificaciones.
- **500 Internal Server Error**: error inesperado.

## Ejemplo cURL
```bash
curl -X POST \
  'http://127.0.0.1:8000/api/v1/usuarios/administrativos/123/reset-password' \
  -H 'Authorization: Bearer {TOKEN}' \
  -H 'Content-Type: application/json'
```

## Configuración
- **Variables de entorno**:
  - `APP_URL_FRONTEND`: URL del frontend (usada en el correo como URL de acceso).
  - `APP_URL`: fallback de `APP_URL_FRONTEND` si no está definida.
- **Configuración de la aplicación**:
  - `config/app.php` define `frontend_url` como `env('APP_URL_FRONTEND', env('APP_URL', 'http://localhost'))`.
  - Tras modificar `.env`, ejecutar `php artisan config:clear`.
- **Correo y colas**:
  - Configurar SES/credenciales y colas (`QUEUE_CONNECTION`) para el envío asíncrono.

## Archivos Relacionados
- `routes/api/v1/usuarios-administrativos.php`: registro de la ruta `POST /{id}/reset-password`.
- `app/Http/Controllers/Api/V1/UserController.php`: método `resetPasswordAdminAndSend`.
- `app/Services/UserService.php`: método `changePasswordAdmin`.
- `app/Jobs/SendEmailJob.php`: job para envío de correo.
- `config/app.php`: clave `frontend_url` (lectura de `APP_URL_FRONTEND`).

## Notas
- La contraseña generada es **temporal y numérica** (6 dígitos); debe cambiarse al iniciar sesión.
- El **usuario** mostrado en el correo corresponde al campo `email` del usuario.
- El correo de notificación es **no-reply** (no debe ser respondido).
