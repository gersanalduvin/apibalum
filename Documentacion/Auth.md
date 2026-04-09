# Documentación API de Autenticación

Esta documentación describe las APIs de autenticación disponibles en el sistema.

## Base URL
```
http://localhost/api/auth
```

## Endpoints Disponibles

### 1. Registro de Usuario

**POST** `/register`

**Descripción:** Registra un nuevo usuario en el sistema.

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "name": "string (requerido)",
    "email": "string (requerido, email válido, único)",
    "password": "string (requerido, mínimo 8 caracteres)",
    "password_confirmation": "string (requerido, debe coincidir con password)"
}
```

**Respuesta Exitosa (201):**
```json
{
    "success": true,
    "message": "Usuario registrado correctamente",
    "user": {
        "id": 1,
        "name": "Nombre Usuario",
        "email": "usuario@ejemplo.com"
    },
    "token": "4|token_de_acceso_aqui"
}
```

**Respuesta de Error (422):**
```json
{
    "success": false,
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "email": ["El email ya está en uso"],
        "password": ["La contraseña debe tener al menos 8 caracteres"]
    }
}
```

---

### 2. Inicio de Sesión

**POST** `/login`

**Descripción:** Autentica un usuario y genera un token de acceso.

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "email": "string (requerido, email válido)",
    "password": "string (requerido)"
}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Login exitoso",
    "user": {
        "id": 1,
        "name": "Nombre Usuario",
        "email": "usuario@ejemplo.com"
    },
    "token": "5|nuevo_token_de_acceso"
}
```

**Respuesta de Error (401):**
```json
{
    "success": false,
    "message": "Las credenciales proporcionadas son incorrectas",
    "errors": {
        "email": ["Las credenciales no coinciden con nuestros registros."]
    }
}
```

---

### 3. Perfil de Usuario (Protegida)

**GET** `/profile`

**Descripción:** Obtiene la información del usuario autenticado.

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Perfil obtenido correctamente",
    "user": {
        "id": 1,
        "name": "Nombre Usuario",
        "email": "usuario@ejemplo.com",
        "email_verified_at": null,
        "created_at": "2025-09-11T23:51:54.000000Z"
    }
}
```

**Respuesta de Error (401):**
```json
{
    "success": false,
    "message": "Usuario no autenticado"
}
```

---

### 4. Cerrar Sesión (Protegida)

**POST** `/logout`

**Descripción:** Revoca el token actual del usuario autenticado.

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Sesión cerrada correctamente"
}
```

**Respuesta de Error (401):**
```json
{
    "success": false,
    "message": "Usuario no autenticado"
}
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Solicitud exitosa |
| 201 | Created - Recurso creado exitosamente |
| 401 | Unauthorized - No autenticado o token inválido |
| 422 | Unprocessable Entity - Errores de validación |
| 500 | Internal Server Error - Error interno del servidor |

## Autenticación

Este sistema utiliza **Laravel Sanctum** para la autenticación basada en tokens.

### Configuración de Tokens
- **Expiración:** 7 días (10080 minutos)
- **Tipo:** Bearer Token
- **Actualización automática:** El campo `last_used_at` se actualiza en cada request

### Uso del Token

Para acceder a rutas protegidas, incluye el token en el header Authorization:

```
Authorization: Bearer {tu_token_aqui}
```

### Ejemplo con cURL

**Registro:**
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Login:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "juan@ejemplo.com",
    "password": "password123"
  }'
```

**Perfil (con token):**
```bash
curl -X GET http://localhost/api/auth/profile \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 4|token_aqui"
```

**Logout:**
```bash
curl -X POST http://localhost/api/auth/logout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 4|token_aqui"
```

## Notas Importantes

1. **Validación:** Todos los endpoints validan los datos de entrada y devuelven errores detallados.
2. **Seguridad:** Los tokens expiran automáticamente después de 7 días.
3. **Limpieza:** Al hacer login, se revocan todos los tokens anteriores del usuario.
4. **CORS:** Asegúrate de configurar CORS correctamente para requests desde frontend.
5. **HTTPS:** En producción, siempre usa HTTPS para proteger los tokens.

## Estructura de Errores

Todos los errores siguen el mismo formato:

```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {
        "campo": ["Mensaje de error específico"]
    }
}
```

---

*Documentación generada para el sistema de autenticación con Laravel Sanctum*