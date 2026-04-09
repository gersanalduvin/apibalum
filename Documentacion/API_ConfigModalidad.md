# API ConfigModalidad

## Descripción
API para la gestión de modalidades de configuración académica. Permite realizar operaciones CRUD sobre las modalidades educativas.

## Base URL
```
/api/v1/config-modalidad
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo `config_modalidad`:

| Endpoint | Método | Permiso Requerido |
|----------|--------|-------------------|
| `/` (listar) | GET | `config_modalidad.index` |
| `/getall` (obtener todos) | GET | `config_modalidad.index` |
| `/` (crear) | POST | `config_modalidad.create` |
| `/{id}` (mostrar) | GET | `config_modalidad.show` |
| `/{id}` (actualizar) | PUT | `config_modalidad.update` |
| `/{id}` (eliminar) | DELETE | `config_modalidad.delete` |
| `/not-synced` | GET | `config_modalidad.sync` |
| `/mark-synced/{uuid}` | POST | `config_modalidad.sync` |
| `/updated-after/{date}` | GET | `config_modalidad.sync` |

**Nota:** Los permisos están organizados bajo la categoría `configuracion_academica` en el sistema de permisos.

## Endpoints

### 1. Listar modalidades (paginado)
**GET** `/api/v1/config-modalidad`

**Parámetros de consulta:**
- `per_page` (opcional): Número de registros por página (default: 15)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "nombre": "Presencial",
                "is_synced": true,
                "synced_at": "2024-01-15T10:30:00Z",
                "updated_locally_at": null,
                "version": 1,
                "created_by": 1,
                "updated_by": null,
                "deleted_by": null,
                "deleted_at": null,
                "cambios": [],
                "created_at": "2024-01-15T10:30:00Z",
                "updated_at": "2024-01-15T10:30:00Z"
            }
        ],
        "per_page": 15,
        "total": 1
    },
    "message": "Modalidades obtenidas exitosamente"
}
```

### 2. Obtener todas las modalidades (sin paginación)
**GET** `/api/v1/config-modalidad/getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Presencial",
            "is_synced": true,
            "synced_at": "2024-01-15T10:30:00Z",
            "updated_locally_at": null,
            "version": 1,
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "deleted_at": null,
            "cambios": [],
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-15T10:30:00Z"
        }
    ],
    "message": "Modalidades obtenidas exitosamente"
}
```

### 3. Crear nueva modalidad
**POST** `/api/v1/config-modalidad`

**Cuerpo de la petición:**
```json
{
    "nombre": "Virtual"
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "nombre": "Virtual",
        "is_synced": false,
        "synced_at": null,
        "updated_locally_at": "2024-01-15T11:00:00Z",
        "version": 1,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "deleted_at": null,
        "cambios": [
            {
                "accion": "creado",
                "usuario": "admin@example.com",
                "fecha": "2024-01-15T11:00:00Z",
                "version": 1
            }
        ],
        "created_at": "2024-01-15T11:00:00Z",
        "updated_at": "2024-01-15T11:00:00Z"
    },
    "message": "Modalidad creada exitosamente"
}
```

### 4. Obtener modalidad específica
**GET** `/api/v1/config-modalidad/{id}`

**Parámetros de ruta:**
- `id`: ID de la modalidad

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Presencial",
        "is_synced": true,
        "synced_at": "2024-01-15T10:30:00Z",
        "updated_locally_at": null,
        "version": 1,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "deleted_at": null,
        "cambios": [],
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
    },
    "message": "Modalidad obtenida exitosamente"
}
```

### 5. Actualizar modalidad
**PUT** `/api/v1/config-modalidad/{id}`

**Parámetros de ruta:**
- `id`: ID de la modalidad

**Cuerpo de la petición:**
```json
{
    "nombre": "Semipresencial"
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único (excepto el registro actual)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Semipresencial",
        "is_synced": false,
        "synced_at": "2024-01-15T10:30:00Z",
        "updated_locally_at": "2024-01-15T12:00:00Z",
        "version": 2,
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "deleted_at": null,
        "cambios": [
            {
                "accion": "actualizado",
                "usuario": "admin@example.com",
                "fecha": "2024-01-15T12:00:00Z",
                "version": 2,
                "cambios": {
                    "nombre": {
                        "anterior": "Presencial",
                        "nuevo": "Semipresencial"
                    }
                }
            }
        ],
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T12:00:00Z"
    },
    "message": "Modalidad actualizada exitosamente"
}
```

### 6. Eliminar modalidad
**DELETE** `/api/v1/config-modalidad/{id}`

**Parámetros de ruta:**
- `id`: ID de la modalidad

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Modalidad eliminada exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 7. Obtener registros no sincronizados
**GET** `/api/v1/config-modalidad/sync/unsynced`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "nombre": "Virtual",
            "is_synced": false,
            "synced_at": null,
            "updated_locally_at": "2024-01-15T11:00:00Z",
            "version": 1
        }
    ],
    "message": "Registros no sincronizados obtenidos exitosamente"
}
```

### 8. Marcar registro como sincronizado
**PATCH** `/api/v1/config-modalidad/sync/{id}/mark-synced`

**Parámetros de ruta:**
- `id`: ID de la modalidad

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "synced": true
    },
    "message": "Registro marcado como sincronizado"
}
```

### 9. Obtener registros actualizados después de una fecha
**GET** `/api/v1/config-modalidad/sync/updated-after?updated_after=2024-01-15T10:00:00Z`

**Parámetros de consulta:**
- `updated_after`: Fecha y hora en formato ISO 8601

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Semipresencial",
            "is_synced": true,
            "synced_at": "2024-01-15T12:30:00Z",
            "updated_locally_at": "2024-01-15T12:00:00Z",
            "version": 2
        }
    ],
    "message": "Registros actualizados obtenidos exitosamente"
}
```

## Códigos de Error

### 400 - Bad Request
```json
{
    "success": false,
    "message": "Error al procesar la solicitud",
    "errors": []
}
```

### 404 - Not Found
```json
{
    "success": false,
    "message": "Modalidad no encontrada",
    "errors": []
}
```

### 422 - Validation Error
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "nombre": [
            "El campo nombre es obligatorio.",
            "El nombre ya existe."
        ]
    }
}
```

### 500 - Internal Server Error
```json
{
    "success": false,
    "message": "Error interno del servidor",
    "errors": []
}
```

## Notas Importantes

1. **UUID**: Cada registro tiene un UUID único generado automáticamente para sincronización offline.
2. **Versionado**: El campo `version` se incrementa automáticamente en cada actualización.
3. **Auditoría**: Los campos `created_by`, `updated_by`, `deleted_by` registran qué usuario realizó cada acción.
4. **Historial**: El campo `cambios` mantiene un historial JSON de todas las modificaciones.
5. **Soft Delete**: Los registros eliminados se marcan con `deleted_at` pero no se eliminan físicamente.
6. **Sincronización**: Los endpoints de sincronización son para uso con aplicaciones PWA offline.