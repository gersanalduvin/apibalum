# API de Configuración de Período Lectivo

## Descripción General

La API de Configuración de Período Lectivo permite gestionar los períodos académicos del sistema educativo, incluyendo la configuración de prefijos e incrementos para diferentes tipos de usuarios (alumnos, docentes, familias y administradores).

**Base URL**: `/api/v1/conf-periodo-lectivo`

**Autenticación**: Todas las rutas requieren autenticación mediante Sanctum (`auth:sanctum`)

## Estructura de Datos

### Modelo ConfPeriodoLectivo

```json
{
  "id": 1,
  "nombre": "Período 2024-2025",
  "prefijo_alumno": "ALU",
  "prefijo_docente": "DOC",
  "prefijo_familia": "FAM",
  "prefijo_admin": "ADM",
  "incremento_alumno": 1000,
  "incremento_docente": 100,
  "incremento_familia": 500,
  "periodo_nota": true,
  "periodo_matricula": false,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "is_synced": true,
  "synced_at": "2024-09-29T13:45:00.000000Z",
  "updated_locally_at": null,
  "version": 1,
  "created_by": 1,
  "updated_by": null,
  "deleted_by": null,
  "cambios": [
    {
      "accion": "creado",
      "usuario_id": 1,
      "fecha": "2024-09-29T13:45:00.000000Z",
      "datos_nuevos": {...}
    }
  ],
  "created_at": "2024-09-29T13:45:00.000000Z",
  "updated_at": "2024-09-29T13:45:00.000000Z",
  "deleted_at": null
}
```

## Endpoints CRUD

### 1. Listar Períodos Lectivos (Paginado)

**GET** `/api/v1/conf-periodo-lectivo`

**Parámetros de consulta:**
- `per_page` (opcional): Número de registros por página (default: 15)
- `search` (opcional): Término de búsqueda

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
    "to": 5,
    "total": 5
  },
  "message": "Períodos lectivos obtenidos exitosamente"
}
```

### 2. Obtener Todos los Períodos (Sin Paginación)

**GET** `/api/v1/conf-periodo-lectivo/getall`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [...],
  "message": "Períodos lectivos obtenidos exitosamente"
}
```

### 3. Crear Período Lectivo

**POST** `/api/v1/conf-periodo-lectivo`

**Cuerpo de la petición:**
```json
{
  "nombre": "Período 2024-2025",
  "prefijo_alumno": "ALU",
  "prefijo_docente": "DOC",
  "prefijo_familia": "FAM",
  "prefijo_admin": "ADM",
  "incremento_alumno": 1000,
  "incremento_docente": 100,
  "incremento_familia": 500,
  "periodo_nota": true,
  "periodo_matricula": false
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único
- `prefijo_alumno`: requerido, string, máximo 10 caracteres
- `prefijo_docente`: requerido, string, máximo 10 caracteres
- `prefijo_familia`: requerido, string, máximo 10 caracteres
- `prefijo_admin`: requerido, string, máximo 10 caracteres
- `incremento_alumno`: requerido, entero, mínimo 1
- `incremento_docente`: requerido, entero, mínimo 1
- `incremento_familia`: requerido, entero, mínimo 1
- `periodo_nota`: opcional, booleano
- `periodo_matricula`: opcional, booleano

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {...},
  "message": "Período lectivo creado exitosamente"
}
```

### 4. Obtener Período Específico

**GET** `/api/v1/conf-periodo-lectivo/{id}`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {...},
  "message": "Período lectivo obtenido exitosamente"
}
```

**Respuesta de error (404):**
```json
{
  "success": false,
  "message": "Período lectivo no encontrado"
}
```

### 5. Actualizar Período Lectivo

**PUT** `/api/v1/conf-periodo-lectivo/{id}`

**Cuerpo de la petición:** (mismo formato que crear)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {...},
  "message": "Período lectivo actualizado exitosamente"
}
```

### 6. Eliminar Período Lectivo

**DELETE** `/api/v1/conf-periodo-lectivo/{id}`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Período lectivo eliminado exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 1. Obtener Períodos No Sincronizados

**GET** `/api/v1/conf-periodo-lectivo/sync/unsynced`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [...],
  "message": "Períodos no sincronizados obtenidos exitosamente"
}
```

### 2. Obtener Períodos Actualizados Después de Fecha

**GET** `/api/v1/conf-periodo-lectivo/sync/updated-after?updated_after=2024-09-29T10:00:00Z`

**Parámetros requeridos:**
- `updated_after`: Fecha y hora en formato ISO 8601

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [...],
  "message": "Períodos actualizados obtenidos exitosamente"
}
```

### 3. Marcar Período como Sincronizado

**POST** `/api/v1/conf-periodo-lectivo/sync/mark-synced/{id}`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Período marcado como sincronizado exitosamente"
}
```

### 4. Sincronizar Período desde Cliente

**POST** `/api/v1/conf-periodo-lectivo/sync/from-client`

**Cuerpo de la petición:**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "nombre": "Período 2024-2025",
  "prefijo_alumno": "ALU",
  "prefijo_docente": "DOC",
  "prefijo_familia": "FAM",
  "prefijo_admin": "ADM",
  "incremento_alumno": 1000,
  "incremento_docente": 100,
  "incremento_familia": 500,
  "periodo_nota": true,
  "periodo_matricula": false,
  "version": 2,
  "is_synced": false,
  "updated_locally_at": "2024-09-29T13:45:00.000000Z"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {...},
  "message": "Período sincronizado exitosamente"
}
```

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Solicitud incorrecta |
| 401 | No autenticado |
| 403 | Sin permisos |
| 404 | Recurso no encontrado |
| 422 | Errores de validación |
| 500 | Error interno del servidor |

## Ejemplo de Respuesta de Error de Validación (422)

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "nombre": ["El nombre es obligatorio", "Ya existe un período lectivo con este nombre"],
    "prefijo_alumno": ["El prefijo de alumno es obligatorio"],
    "incremento_alumno": ["El incremento de alumno debe ser mayor a 0"],
    "periodo_nota": ["El período de nota debe ser verdadero o falso"],
    "periodo_matricula": ["El período de matrícula debe ser verdadero o falso"]
  }
}
```

## Características Especiales

### Auditoría
- Todos los cambios se registran en el campo `cambios`
- Se almacena el usuario que realizó cada acción
- Se mantiene un historial completo de modificaciones

### Soft Delete
- Los registros eliminados no se borran físicamente
- Se marcan con `deleted_at` y `deleted_by`

### Sincronización Offline
- Cada registro tiene un `uuid` único para sincronización
- Control de versiones con el campo `version`
- Resolución automática de conflictos basada en versiones

### Búsqueda
- Búsqueda por nombre y prefijos
- Soporte para paginación en resultados de búsqueda