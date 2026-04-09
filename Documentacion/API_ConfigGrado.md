# API ConfigGrado

## Descripción
API para la gestión de grados de configuración académica. Permite realizar operaciones CRUD sobre los grados educativos.

## Base URL
```
/api/v1/config-grado
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo `config_grado`:

| Endpoint | Método | Permiso Requerido |
|----------|--------|-------------------|
| `/` (listar) | GET | `config_grado.index` |
| `/getall` (obtener todos) | GET | `config_grado.index` |
| `/` (crear) | POST | `config_grado.create` |
| `/{id}` (mostrar) | GET | `config_grado.show` |
| `/{id}` (actualizar) | PUT | `config_grado.update` |
| `/{id}` (eliminar) | DELETE | `config_grado.delete` |
| `/not-synced` | GET | `config_grado.sync` |
| `/mark-synced/{uuid}` | POST | `config_grado.sync` |
| `/updated-after/{date}` | GET | `config_grado.sync` |
| `/opciones/modalidades` | GET | `config_grado.index` |

**Nota:** Los permisos están organizados bajo la categoría `configuracion_academica` en el sistema de permisos.

## Endpoints

### 1. Listar grados (paginado)
**GET** `/api/v1/config-grado`

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
                "nombre": "Primer Grado",
                "abreviatura": "1°",
                "orden": 1,
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
    "message": "Grados obtenidos exitosamente"
}
```

### 2. Obtener todos los grados (sin paginación)
**GET** `/api/v1/config-grado/getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Primer Grado",
            "abreviatura": "1°",
            "orden": 1,
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
    "message": "Grados obtenidos exitosamente"
}
```

### 3. Crear nuevo grado
**POST** `/api/v1/config-grado`

**Cuerpo de la petición:**
```json
{
    "nombre": "Segundo Grado",
    "abreviatura": "2°",
    "orden": 2,
    "modalidad_id": 1
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único
- `abreviatura`: requerido, string, máximo 10 caracteres, único
- `orden`: requerido, entero, único
- `modalidad_id`: requerido, entero, debe existir en `config_modalidad.id`

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "nombre": "Segundo Grado",
        "abreviatura": "2°",
        "orden": 2,
        "modalidad_id": 1,
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
    "message": "Grado creado exitosamente"
}
```

### 4. Obtener grado específico
**GET** `/api/v1/config-grado/{id}`

**Parámetros de ruta:**
- `id`: ID del grado

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Primer Grado",
        "abreviatura": "1°",
        "orden": 1,
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
    "message": "Grado obtenido exitosamente"
}
```

### 5. Actualizar grado
**PUT** `/api/v1/config-grado/{id}`

**Parámetros de ruta:**
- `id`: ID del grado

**Cuerpo de la petición:**
```json
{
    "nombre": "Primer Grado de Primaria",
    "abreviatura": "1° Prim",
    "orden": 1,
    "modalidad_id": 1
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único (excepto el registro actual)
- `abreviatura`: requerido, string, máximo 10 caracteres, único (excepto el registro actual)
- `orden`: requerido, entero, único (excepto el registro actual)
- `modalidad_id`: requerido, entero, debe existir en `config_modalidad.id`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Primer Grado de Primaria",
        "abreviatura": "1° Prim",
        "orden": 1,
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
                        "anterior": "Primer Grado",
                        "nuevo": "Primer Grado de Primaria"
                    },
                    "abreviatura": {
                        "anterior": "1°",
                        "nuevo": "1° Prim"
                    }
                }
            }
        ],
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T12:00:00Z"
    },
    "message": "Grado actualizado exitosamente"
}
```

### 6. Eliminar grado
**DELETE** `/api/v1/config-grado/{id}`

**Parámetros de ruta:**
- `id`: ID del grado

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Grado eliminado exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 7. Obtener registros no sincronizados
**GET** `/api/v1/config-grado/sync/unsynced`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "nombre": "Segundo Grado",
            "abreviatura": "2°",
            "orden": 2,
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
**PATCH** `/api/v1/config-grado/sync/{id}/mark-synced`

**Parámetros de ruta:**
- `id`: ID del grado

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
**GET** `/api/v1/config-grado/sync/updated-after?updated_after=2024-01-15T10:00:00Z`

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
            "nombre": "Primer Grado de Primaria",
            "abreviatura": "1° Prim",
            "orden": 1,
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
    "message": "Grado no encontrado",
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
        ],
        "abreviatura": [
            "El campo abreviatura es obligatorio.",
            "La abreviatura ya existe."
        ],
        "orden": [
            "El campo orden es obligatorio.",
            "El orden ya existe."
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
7. **Orden**: El campo `orden` permite establecer una secuencia lógica de los grados.
8. **Abreviatura**: Campo útil para mostrar versiones cortas del nombre del grado en interfaces compactas.
### 10. Listar Modalidades
**GET** `/api/v1/config-grado/opciones/modalidades`

**Descripción:** Obtiene una lista de todas las modalidades disponibles para controles select.

**Permisos requeridos:** `config_grado.index`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    { "id": 1, "nombre": "Regular" },
    { "id": 2, "nombre": "Semipresencial" }
  ],
  "message": "Modalidades obtenidas exitosamente"
}
```