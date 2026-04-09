# API de Cambio de Contraseña

## Descripción
Esta API permite a los usuarios autenticados cambiar su contraseña actual por una nueva. Solo requiere que el usuario esté autenticado, sin necesidad de permisos específicos.

## Endpoint

### Cambiar Contraseña
**PUT** `/api/v1/users/change-password`

#### Autenticación
- **Requerida**: Sí
- **Tipo**: Bearer Token (Sanctum)
- **Middleware**: `auth:sanctum`

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Parámetros del Request
```json
{
    "current_password": "string",
    "new_password": "string",
    "new_password_confirmation": "string"
}
```

#### Validaciones
- **current_password**: 
  - Requerido
  - Debe coincidir con la contraseña actual del usuario
- **new_password**: 
  - Requerido
  - Mínimo 8 caracteres
  - Debe ser diferente a la contraseña actual
  - Debe coincidir con la confirmación
- **new_password_confirmation**: 
  - Requerido
  - Debe coincidir con `new_password`

#### Respuestas

##### Éxito (200 OK)
```json
{
    "success": true,
    "data": null,
    "message": "Contraseña cambiada exitosamente"
}
```

##### Error de Validación (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "current_password": ["La contraseña actual no es correcta."],
        "new_password": ["La nueva contraseña debe tener al menos 8 caracteres."],
        "new_password_confirmation": ["La confirmación de la nueva contraseña no coincide."]
    }
}
```

##### Error de Autenticación (401 Unauthorized)
```json
{
    "success": false,
    "message": "No autenticado"
}
```

##### Error del Servidor (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error al cambiar la contraseña: [detalle del error]",
    "errors": []
}
```

## Ejemplo de Uso

### cURL
```bash
curl -X PUT http://localhost:8081/api/v1/users/change-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu_token_aqui" \
  -d '{
    "current_password": "contraseña_actual",
    "new_password": "nueva_contraseña_123",
    "new_password_confirmation": "nueva_contraseña_123"
  }'
```

### JavaScript (Fetch)
```javascript
const response = await fetch('/api/v1/users/change-password', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        current_password: 'contraseña_actual',
        new_password: 'nueva_contraseña_123',
        new_password_confirmation: 'nueva_contraseña_123'
    })
});

const result = await response.json();
```

## Características de Seguridad

### Validación de Contraseña Actual
- Se verifica que la contraseña actual proporcionada coincida con la almacenada en la base de datos
- Utiliza `Hash::check()` para comparación segura

### Encriptación
- La nueva contraseña se encripta usando `Hash::make()` antes de almacenarla
- No se almacenan contraseñas en texto plano

### Auditoría
- Se registra el cambio de contraseña en el historial de cambios del usuario
- Se incluye información del usuario que realizó el cambio y la fecha/hora
- Las contraseñas no se almacenan en el historial por seguridad

### Transacciones
- Todo el proceso se ejecuta dentro de una transacción de base de datos
- Si ocurre algún error, se revierte automáticamente

## Notas Importantes

1. **Solo el usuario autenticado puede cambiar su propia contraseña**
2. **No se requieren permisos específicos**, solo autenticación
3. **La contraseña actual debe ser proporcionada** para confirmar la identidad
4. **La nueva contraseña debe ser diferente** a la actual
5. **Se mantiene un historial de cambios** para auditoría
6. **El proceso es transaccional** para garantizar consistencia

## Archivos Relacionados

- **Controller**: `app/Http/Controllers/Api/V1/UserController.php`
- **Service**: `app/Services/UserService.php`
- **Request**: `app/Http/Requests/Api/V1/PasswordChangeRequest.php`
- **Rutas**: `routes/api/v1/users.php`