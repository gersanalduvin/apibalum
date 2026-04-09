# Configuración CORS en Laravel 11

## ¿Qué es CORS?

CORS (Cross-Origin Resource Sharing) es un mecanismo de seguridad que permite o restringe las solicitudes HTTP realizadas desde un dominio diferente al del servidor. Es esencial para aplicaciones web que necesitan comunicarse con APIs desde diferentes orígenes.

## Problema Resuelto

Si experimentabas errores de CORS al intentar hacer login desde otro cliente, esto se debía a que Laravel no tenía configurado el middleware CORS correctamente.

## Configuración Implementada

### 1. Middleware CORS en bootstrap/app.php

```php
->withMiddleware(function (Middleware $middleware): void {
    // Configuración de CORS
    $middleware->api([
        \Illuminate\Http\Middleware\HandleCors::class,
    ]);
})
```

### 2. Archivo de Configuración config/cors.php

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

## Configuración Actual (Desarrollo)

- **Rutas protegidas**: `api/*` y `sanctum/csrf-cookie`
- **Métodos permitidos**: Todos (`GET`, `POST`, `PUT`, `DELETE`, etc.)
- **Orígenes permitidos**: Configurados por variable de entorno
- **Headers permitidos**: Todos
- **Credenciales**: Habilitadas (`supports_credentials: true`)

### Variable de Entorno CORS_ALLOWED_ORIGINS

En el archivo `.env`:
```env
CORS_ALLOWED_ORIGINS=https://127.0.0.1:3001,http://localhost:8081,http://127.0.0.1:8081
```

Esta configuración permite:
- `https://127.0.0.1:3001` - Frontend principal (HTTPS)
- `http://localhost:8081` - Frontend alternativo en puerto 8081
- `http://127.0.0.1:8081` - Frontend alternativo con IP local

### Configuración en config/cors.php

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3001,http://127.0.0.1:3001')),
'supports_credentials' => true,
```

## Configuración para Producción

Para producción, debes restringir los orígenes permitidos:

```php
'allowed_origins' => [
    'https://tu-frontend.com',
    'https://app.tu-dominio.com',
],

// O usar patrones
'allowed_origins_patterns' => [
    '/^https:\/\/.*\.tu-dominio\.com$/',
],

// Habilitar credenciales si usas cookies/sesiones
'supports_credentials' => true,
```

## Comandos Útiles

```bash
# Limpiar caché de configuración
php artisan config:clear

# Ver configuración actual
php artisan config:show cors

# Cachear configuración (producción)
php artisan config:cache
```

## Pruebas de CORS

### Desde JavaScript (Frontend)

```javascript
// Ejemplo de petición desde otro dominio
fetch('http://127.0.0.1:8000/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'usuario@ejemplo.com',
        password: 'password'
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Desde cURL

```bash
# Prueba de preflight request
curl -X OPTIONS http://127.0.0.1:8000/api/auth/login \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v

# Petición real
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:3000" \
  -d '{"email":"test@example.com","password":"password"}' \
  -v
```

## Headers CORS Importantes

- **Access-Control-Allow-Origin**: Especifica qué orígenes pueden acceder
- **Access-Control-Allow-Methods**: Métodos HTTP permitidos
- **Access-Control-Allow-Headers**: Headers permitidos en las peticiones
- **Access-Control-Allow-Credentials**: Si se permiten cookies/credenciales
- **Access-Control-Max-Age**: Tiempo de caché para preflight requests

## Troubleshooting

### Error: "CORS policy: No 'Access-Control-Allow-Origin' header"

**Causas comunes:**
1. Middleware no registrado correctamente
2. Configuración en caché desactualizada
3. Servidor corriendo solo en localhost (127.0.0.1)

**Soluciones:**
1. Verifica que el middleware esté registrado en `bootstrap/app.php`
2. Limpia la caché: `php artisan config:clear`
3. Revisa que la ruta esté en `paths` del archivo cors.php
4. **Si accedes desde otra IP de la red:** Inicia el servidor con `php artisan serve --host=0.0.0.0 --port=8000`

### Error: "CORS policy: Request header field X is not allowed"

- Agrega el header específico a `allowed_headers` o usa `['*']`

### Error: "CORS policy: Method X is not allowed"

- Verifica que el método esté en `allowed_methods`

### Error: Acceso desde IP diferente (192.168.x.x)

**Problema:** El servidor Laravel por defecto solo escucha en `127.0.0.1` (localhost)

**Solución:**
```bash
# En lugar de:
php artisan serve

# Usa:
php artisan serve --host=0.0.0.0 --port=8000
```

Esto permite conexiones desde cualquier IP de la red local.

### Error: CORS con rutas protegidas por Sanctum

**Problema:** Las rutas protegidas por `auth:sanctum` (como `/api/user`) pueden generar errores CORS si no se envía el token correctamente.

**Causa:** La ruta `/api/user` requiere autenticación:
```php
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
```

**Soluciones:**

1. **Incluir token en las peticiones:**
```javascript
fetch('http://localhost:8000/api/user', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})
```

2. **Verificar que el token sea válido:**
```bash
# Probar con token
curl -H "Authorization: Bearer tu_token_aqui" \
     -H "Origin: http://192.168.1.2:3000" \
     http://localhost:8000/api/user
```

3. **Usar rutas de prueba sin autenticación:**
- `/api/cors-test` - Prueba básica de CORS
- `/api/user-test` - Prueba de user sin autenticación

## Seguridad

⚠️ **Importante para Producción**:

1. **Nunca uses `'*'` en `allowed_origins` en producción**
2. **Especifica dominios exactos o patrones seguros**
3. **Habilita `supports_credentials` solo si es necesario**
4. **Revisa regularmente los logs de CORS**

## Estado Actual

✅ **Configuración CORS implementada y funcionando**
✅ **Servidor corriendo en http://0.0.0.0:8000** (accesible desde cualquier IP de la red)
✅ **Middleware registrado correctamente**
✅ **Configuración permisiva para desarrollo**
✅ **Problema de acceso desde IP diferente resuelto**
✅ **Problema con rutas Sanctum identificado y documentado**
✅ **Rutas de prueba CORS disponibles**

### Rutas de Prueba Disponibles:
- `GET /api/cors-test` - Prueba básica de CORS
- `GET /api/user-test` - Prueba de user sin autenticación
- `GET /api/user` - Ruta original (requiere token Sanctum)

**El problema de CORS está resuelto.** Si aún tienes errores, verifica que estés enviando el token de autenticación correctamente en las rutas protegidas.