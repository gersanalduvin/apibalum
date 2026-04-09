# API Config Formas de Pago

## Descripción
API para la gestión de formas de pago del sistema. Permite realizar operaciones CRUD, búsquedas y sincronización de datos.

## URL Base
```
/api/v1/config-formas-pago
```

## Autenticación
Todas las rutas requieren autenticación mediante token Bearer y permisos específicos.

## Permisos Requeridos

Los siguientes permisos están definidos en el sistema para este módulo:

- `config_formas_pago.index` - Ver listado de formas de pago
- `config_formas_pago.show` - Ver detalles de una forma de pago específica  
- `config_formas_pago.create` - Crear nuevas formas de pago
- `config_formas_pago.update` - Actualizar formas de pago existentes
- `config_formas_pago.delete` - Eliminar formas de pago
- `config_formas_pago.sync` - Sincronizar datos (modo offline)
- `config_formas_pago.search` - Buscar formas de pago

## Endpoints

### 1. Listar Formas de Pago (Paginado)
```http
GET /api/v1/config-formas-pago
```

**Permisos:** `config_formas_pago.index`

**Parámetros de consulta:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15, max: 100)
- `search` (opcional): Término de búsqueda (nombre o abreviatura)
- `activo` (opcional): Filtrar por estado (true/false)

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
                "nombre": "Efectivo",
                "abreviatura": "EFE",
                "activo": true,
                "created_by": 1,
                "updated_by": null,
                "deleted_by": null,
                "is_synced": true,
                "synced_at": "2024-01-15T10:30:00.000000Z",
                "updated_locally_at": null,
                "version": 1,
                "cambios": [...],
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "deleted_at": null
            }
        ],
        "first_page_url": "http://localhost:8000/api/v1/config-formas-pago?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/v1/config-formas-pago?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost:8000/api/v1/config-formas-pago",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Formas de pago obtenidas exitosamente"
}
```

### 2. Listar Todas las Formas de Pago (Sin Paginación)
**GET** `/api/v1/config-formas-pago/getall`

**Descripción:** Obtiene todas las formas de pago sin paginación.

**Parámetros de consulta:**
- `active` (opcional): Filtrar por estado activo (true/false)

**Permisos requeridos:**
- `config-formas-pago.getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Efectivo",
            "abreviatura": "EFE",
            "activo": true,
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "is_synced": true,
            "synced_at": "2024-01-15T10:30:00.000000Z",
            "updated_locally_at": null,
            "version": 1,
            "cambios": [...],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "deleted_at": null
        }
    ],
    "message": "Formas de pago obtenidas exitosamente"
}
```

### 3. Crear Forma de Pago
**POST** `/api/v1/config-formas-pago`

**Descripción:** Crea una nueva forma de pago.

**Permisos requeridos:**
- `config-formas-pago.store`

**Cuerpo de la petición:**
```json
{
    "nombre": "Tarjeta de Crédito",
    "abreviatura": "TC",
    "activo": true
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres
- `abreviatura`: requerido, string, máximo 10 caracteres
- `activo`: opcional, boolean (default: true)

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "nombre": "Tarjeta de Crédito",
        "abreviatura": "TC",
        "activo": true,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "is_synced": false,
        "synced_at": null,
        "updated_locally_at": null,
        "version": 1,
        "cambios": [
            {
                "accion": "creado",
                "usuario_email": "admin@example.com",
                "fecha": "2024-01-15T10:35:00",
                "datos_anteriores": null,
                "datos_nuevos": {
                    "nombre": "Tarjeta de Crédito",
                    "abreviatura": "TC",
                    "activo": true
                }
            }
        ],
        "created_at": "2024-01-15T10:35:00.000000Z",
        "updated_at": "2024-01-15T10:35:00.000000Z",
        "deleted_at": null
    },
    "message": "Forma de pago creada exitosamente"
}
```

### 4. Obtener Forma de Pago por ID/UUID
**GET** `/api/v1/config-formas-pago/{id}`

**Descripción:** Obtiene una forma de pago específica por ID numérico o UUID.

**Parámetros de ruta:**
- `id`: ID numérico o UUID de la forma de pago

**Permisos requeridos:**
- `config-formas-pago.show`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Efectivo",
        "abreviatura": "EFE",
        "activo": true,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "is_synced": true,
        "synced_at": "2024-01-15T10:30:00.000000Z",
        "updated_locally_at": null,
        "version": 1,
        "cambios": [...],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Forma de pago obtenida exitosamente"
}
```

### 5. Actualizar Forma de Pago
**PUT** `/api/v1/config-formas-pago/{id}`

**Descripción:** Actualiza una forma de pago existente.

**Parámetros de ruta:**
- `id`: ID numérico de la forma de pago

**Permisos requeridos:**
- `config-formas-pago.update`

**Cuerpo de la petición:**
```json
{
    "nombre": "Efectivo Actualizado",
    "abreviatura": "EFE",
    "activo": false
}
```

**Validaciones:**
- `nombre`: opcional, string, máximo 255 caracteres
- `abreviatura`: opcional, string, máximo 10 caracteres
- `activo`: opcional, boolean

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Efectivo Actualizado",
        "abreviatura": "EFE",
        "activo": false,
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "is_synced": false,
        "synced_at": "2024-01-15T10:30:00.000000Z",
        "updated_locally_at": null,
        "version": 2,
        "cambios": [
            {
                "accion": "creado",
                "usuario_email": "admin@example.com",
                "fecha": "2024-01-15T10:30:00",
                "datos_anteriores": null,
                "datos_nuevos": {...}
            },
            {
                "accion": "actualizado",
                "usuario_email": "admin@example.com",
                "fecha": "2024-01-15T10:40:00",
                "datos_anteriores": {
                    "nombre": "Efectivo",
                    "abreviatura": "EFE",
                    "activo": true
                },
                "datos_nuevos": {
                    "nombre": "Efectivo Actualizado",
                    "abreviatura": "EFE",
                    "activo": false
                }
            }
        ],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:40:00.000000Z",
        "deleted_at": null
    },
    "message": "Forma de pago actualizada exitosamente"
}
```

### 6. Eliminar Forma de Pago
**DELETE** `/api/v1/config-formas-pago/{id}`

**Descripción:** Elimina una forma de pago (eliminación lógica).

**Parámetros de ruta:**
- `id`: ID numérico de la forma de pago

**Permisos requeridos:**
- `config-formas-pago.destroy`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Forma de pago eliminada exitosamente"
}
```

### 7. Buscar Formas de Pago
**GET** `/api/v1/config-formas-pago/search/term`

**Descripción:** Busca formas de pago por nombre o abreviatura.

**Parámetros de consulta:**
- `term`: Término de búsqueda (requerido)
- `per_page` (opcional): Número de registros por página (default: 15)

**Permisos requeridos:**
- `config-formas-pago.search`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 1,
        "last_page_url": "...",
        "links": [...],
        "next_page_url": null,
        "path": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Búsqueda realizada exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 8. Obtener Registros No Sincronizados
**GET** `/api/v1/config-formas-pago/sync/unsynced`

**Descripción:** Obtiene todos los registros que no han sido sincronizados.

**Permisos requeridos:**
- `config-formas-pago.sync`

### 9. Marcar Registro como Sincronizado
**PUT** `/api/v1/config-formas-pago/sync/{id}/mark-synced`

**Descripción:** Marca un registro como sincronizado.

**Permisos requeridos:**
- `config-formas-pago.sync`

### 10. Obtener Registros Actualizados Después de una Fecha
**GET** `/api/v1/config-formas-pago/sync/updated-after`

**Descripción:** Obtiene registros actualizados después de una fecha específica.

**Parámetros de consulta:**
- `datetime`: Fecha y hora en formato ISO 8601 (requerido)

**Permisos requeridos:**
- `config-formas-pago.sync`

## Códigos de Error

### 400 - Bad Request
```json
{
    "success": false,
    "message": "Error al crear la forma de pago: Ya existe una forma de pago con este nombre",
    "data": null
}
```

### 404 - Not Found
```json
{
    "success": false,
    "message": "Error al obtener la forma de pago: Forma de pago no encontrada",
    "data": null
}
```

### 422 - Validation Error
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "nombre": ["El nombre es obligatorio"],
        "abreviatura": ["La abreviatura es obligatoria"]
    }
}
```

### 500 - Internal Server Error
```json
{
    "success": false,
    "message": "Error al obtener las formas de pago: Error interno del servidor",
    "data": null
}
```

## Permisos Necesarios

Para utilizar esta API, el usuario debe tener los siguientes permisos:

- `config-formas-pago.index` - Ver listado paginado
- `config-formas-pago.getall` - Ver listado completo
- `config-formas-pago.store` - Crear forma de pago
- `config-formas-pago.show` - Ver forma de pago específica
- `config-formas-pago.update` - Actualizar forma de pago
- `config-formas-pago.destroy` - Eliminar forma de pago
- `config-formas-pago.search` - Buscar formas de pago
- `config-formas-pago.sync` - Operaciones de sincronización

## Notas Importantes

1. **Bitácora de Cambios**: Todos los cambios (creación, actualización, eliminación) se registran en el campo `cambios` con información detallada del usuario, fecha y datos modificados.

2. **Eliminación Lógica**: Las formas de pago no se eliminan físicamente de la base de datos, sino que se marcan como eliminadas usando `deleted_at`.

3. **Sincronización**: El sistema incluye campos para manejo de sincronización offline (`is_synced`, `synced_at`, `updated_locally_at`, `version`).

4. **Validación de Unicidad**: No se permiten formas de pago con el mismo nombre o abreviatura.

5. **UUID**: Cada registro tiene un UUID único para facilitar la sincronización entre diferentes instancias.

6. **Campos de Auditoría**: Se registra quién creó, actualizó y eliminó cada registro (`created_by`, `updated_by`, `deleted_by`).