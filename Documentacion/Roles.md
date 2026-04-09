# API de Roles

## Descripción General
La API de Roles permite gestionar los roles del sistema, incluyendo la asignación de permisos específicos a cada rol. Implementa operaciones CRUD completas con funcionalidades adicionales como búsqueda, filtrado por permisos y restauración de roles eliminados.

## Estructura de Archivos
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── RoleController.php
│   └── Requests/
│       └── Api/
│           └── V1/
│               └── RoleRequest.php
├── Services/
│   └── RoleService.php
├── Repositories/
│   └── RoleRepository.php
└── Models/
    └── Role.php
```

## Endpoints

### 1. Listar Roles (Paginado)
**GET** `/api/v1/roles`

**Middleware:** `check.permissions:roles.ver`

**Parámetros de consulta:**
- `page` (opcional): Número de página (por defecto: 1)
- `per_page` (opcional): Elementos por página (por defecto: 10, máximo: 100)

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "nombre": "Administrador",
                "permisos": ["roles.ver", "roles.crear", "roles.editar"],
                "created_by": 1,
                "updated_by": null,
                "deleted_by": null,
                "cambios": [],
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "deleted_at": null
            }
        ],
        "first_page_url": "http://localhost/api/v1/roles?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/v1/roles?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/v1/roles",
        "per_page": 10,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Roles obtenidos exitosamente"
}
```

### 2. Listar Todos los Roles (Sin Paginación)
**GET** `/api/v1/roles/all`

**Middleware:** `check.permissions:roles.ver`

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Administrador",
            "permisos": ["roles.ver", "roles.crear", "roles.editar"],
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "cambios": [],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "deleted_at": null
        }
    ],
    "message": "Roles obtenidos exitosamente"
}
```

### 3. Obtener Rol Específico
**GET** `/api/v1/roles/{id}`

**Middleware:** `check.permissions:roles.ver`

**Parámetros de ruta:**
- `id` (requerido): ID del rol

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Administrador",
        "permisos": ["roles.ver", "roles.crear", "roles.editar"],
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "cambios": [],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Rol obtenido exitosamente"
}
```

### 4. Crear Rol
**POST** `/api/v1/roles`

**Middleware:** `check.permissions:roles.crear`

**Cuerpo de la solicitud:**
```json
{
    "nombre": "Editor",
    "permisos": ["contenido.ver", "contenido.editar"]
}
```

**Campos:**
- `nombre` (requerido): Nombre del rol (string, máximo 255 caracteres, solo letras y espacios)
- `permisos` (opcional): Array de permisos (array de strings)

**Respuesta exitosa (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "nombre": "Editor",
        "permisos": ["contenido.ver", "contenido.editar"],
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "cambios": [],
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T11:00:00.000000Z",
        "deleted_at": null
    },
    "message": "Rol creado exitosamente"
}
```

### 5. Actualizar Rol
**PUT** `/api/v1/roles/{id}`

**Middleware:** `check.permissions:roles.editar`

**Parámetros de ruta:**
- `id` (requerido): ID del rol

**Cuerpo de la solicitud:**
```json
{
    "nombre": "Editor Avanzado",
    "permisos": ["contenido.ver", "contenido.editar", "contenido.crear"]
}
```

**Campos:**
- `nombre` (opcional): Nombre del rol (string, máximo 255 caracteres, solo letras y espacios)
- `permisos` (opcional): Array de permisos (array de strings)

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "nombre": "Editor Avanzado",
        "permisos": ["contenido.ver", "contenido.editar", "contenido.crear"],
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "cambios": [
            {
                "campo": "nombre",
                "valor_anterior": "Editor",
                "valor_nuevo": "Editor Avanzado",
                "usuario_id": 1,
                "fecha": "2024-01-15T11:30:00"
            }
        ],
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T11:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Rol actualizado exitosamente"
}
```

### 6. Eliminar Rol (Soft Delete)
**DELETE** `/api/v1/roles/{id}`

**Middleware:** `check.permissions:roles.eliminar`

**Parámetros de ruta:**
- `id` (requerido): ID del rol

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": null,
    "message": "Rol eliminado exitosamente"
}
```

### 7. Buscar Roles
**GET** `/api/v1/roles/search/query`

**Middleware:** `check.permissions:roles.ver`

**Parámetros de consulta:**
- `q` (requerido): Término de búsqueda
- `page` (opcional): Número de página
- `per_page` (opcional): Elementos por página

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "nombre": "Administrador",
                "permisos": ["roles.ver", "roles.crear"],
                "created_by": 1,
                "updated_by": null,
                "deleted_by": null,
                "cambios": [],
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "deleted_at": null
            }
        ],
        "total": 1
    },
    "message": "Búsqueda completada exitosamente"
}
```

### 8. Filtrar Roles por Permisos
**POST** `/api/v1/roles/by-permissions`

**Middleware:** `check.permissions:roles.ver`

**Cuerpo de la solicitud:**
```json
{
    "permisos": ["roles.ver", "roles.crear"]
}
```

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Administrador",
            "permisos": ["roles.ver", "roles.crear", "roles.editar"],
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "cambios": [],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "deleted_at": null
        }
    ],
    "message": "Roles filtrados exitosamente"
}
```

### 9. Restaurar Rol Eliminado
**POST** `/api/v1/roles/{id}/restore`

**Middleware:** `check.permissions:roles.editar`

**Parámetros de ruta:**
- `id` (requerido): ID del rol eliminado

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "nombre": "Editor",
        "permisos": ["contenido.ver", "contenido.editar"],
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "cambios": [],
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T12:00:00.000000Z",
        "deleted_at": null
    },
    "message": "Rol restaurado exitosamente"
}
```

### 10. Obtener Permisos Disponibles
**GET** `/api/v1/roles/permissions/available`

**Middleware:** `check.permissions:permisos.ver`

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "configuracion": [
            {
                "permission": "roles.ver",
                "description": "Ver roles"
            },
            {
                "permission": "roles.crear",
                "description": "Crear roles"
            }
        ],
        "gestion": [
            {
                "permission": "usuarios.ver",
                "description": "Ver usuarios"
            }
        ]
    },
    "message": "Permisos disponibles obtenidos exitosamente"
}
```

## Validaciones

### Campos del Rol
- **nombre**: 
  - Requerido para creación
  - Opcional para actualización
  - Tipo: string
  - Máximo: 255 caracteres
  - Patrón: Solo letras y espacios
  - Único: No puede repetirse (excluyendo eliminados)

- **permisos**:
  - Opcional
  - Tipo: array de strings
  - Cada permiso debe existir en el sistema
  - Máximo: 20 permisos por rol
  - Se eliminan duplicados automáticamente

### Validaciones Personalizadas
- No se pueden asignar permisos de eliminación sin permisos de visualización
- Los permisos inválidos son filtrados automáticamente
- El nombre se normaliza (trim) antes de la validación

## Respuestas de Error

### Error de Validación (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "nombre": ["El nombre del rol es obligatorio."],
        "permisos": ["El permiso seleccionado no es válido."]
    }
}
```

### Rol No Encontrado (404 Not Found)
```json
{
    "success": false,
    "message": "Rol no encontrado"
}
```

### Sin Permisos (403 Forbidden)
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción"
}
```

### Error del Servidor (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error interno del servidor"
}
```

## Permisos Requeridos

- **roles.ver**: Ver roles (index, show, search, byPermissions)
- **roles.crear**: Crear roles (store)
- **roles.editar**: Editar roles (update, restore)
- **roles.eliminar**: Eliminar roles (destroy)
- **permisos.ver**: Ver permisos disponibles (availablePermissions)

## Estructura del Array de Permisos

Los permisos se almacenan como un array de strings con la convención `{módulo}.{acción}`:

### Ejemplos:
```json
// Rol básico
{
    "permisos": ["contenido.ver"]
}

// Rol completo
{
    "permisos": [
        "roles.ver",
        "roles.crear", 
        "roles.editar",
        "usuarios.ver",
        "contenido.ver",
        "contenido.editar"
    ]
}

// Rol sin permisos
{
    "permisos": []
}
```

### Permisos Disponibles por Categoría:

**Configuración:**
- `roles.ver`, `roles.crear`, `roles.editar`, `roles.eliminar`
- `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.eliminar`
- `permisos.ver`

**Gestión:**
- `contenido.ver`, `contenido.crear`, `contenido.editar`, `contenido.eliminar`
- `categorias.ver`, `categorias.crear`, `categorias.editar`, `categorias.eliminar`

**Reportes:**
- `reportes.ver`, `reportes.generar`, `reportes.exportar`

## Ejemplos de Uso con cURL

### Crear un rol
```bash
curl -X POST http://localhost/api/v1/roles \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "nombre": "Editor",
    "permisos": ["contenido.ver", "contenido.editar"]
  }'
```

### Actualizar un rol
```bash
curl -X PUT http://localhost/api/v1/roles/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "nombre": "Super Editor",
    "permisos": ["contenido.ver", "contenido.editar", "contenido.crear"]
  }'
```

### Buscar roles
```bash
curl -X GET "http://localhost/api/v1/roles/search/query?q=admin" \
  -H "Authorization: Bearer {token}"
```

## Middleware de Permisos

Todos los endpoints están protegidos por el middleware `check.permissions` que verifica:
1. Usuario autenticado
2. Permisos específicos requeridos para cada acción
3. Estado activo del usuario y rol

## Historial de Cambios

El sistema registra automáticamente todos los cambios realizados en los roles:
- Campo modificado
- Valor anterior y nuevo
- Usuario que realizó el cambio
- Fecha y hora del cambio

## Notas Importantes

1. **Soft Delete**: Los roles eliminados no se borran físicamente, se marcan como eliminados
2. **Auditoría**: Todos los cambios quedan registrados en el campo `cambios`
3. **Permisos Dinámicos**: Los permisos se validan contra el servicio de permisos en tiempo real
4. **Normalización**: Los nombres se normalizan automáticamente
5. **Límites**: Máximo 20 permisos por rol para mantener rendimiento

## Códigos de Estado HTTP

- **200 OK**: Operación exitosa
- **201 Created**: Recurso creado exitosamente
- **400 Bad Request**: Solicitud malformada
- **401 Unauthorized**: No autenticado
- **403 Forbidden**: Sin permisos
- **404 Not Found**: Recurso no encontrado
- **422 Unprocessable Entity**: Errores de validación
- **500 Internal Server Error**: Error del servidor