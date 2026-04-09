# API Documentation - Config Grupos

## Descripción
API para gestionar la configuración de grupos académicos, incluyendo información de período lectivo, grado, sección, turno, modalidad y docente guía.

## Base URL
```
/api/v1/config-grupos
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Endpoints

### 1. Listar Config Grupos (Paginado)
**GET** `/api/v1/config-grupos`

**Descripción:** Obtiene una lista paginada de grupos de configuración.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de consulta:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "nombre": "A",
                "periodo_lectivo_id": 1,
                "grado_id": 1,
                "seccion_id": 1,
                "turno_id": 1,
                "modalidad_id": 1,
                "docente_guia": 1,
                "activo": true,
                "created_at": "2024-01-15T10:00:00.000000Z",
                "updated_at": "2024-01-15T10:00:00.000000Z",
                "periodo_lectivo": {
                    "id": 1,
                    "nombre": "2024-1"
                },
                "grado": {
                    "id": 1,
                    "nombre": "Primero"
                },
                "seccion": {
                    "id": 1,
                    "nombre": "A"
                },
                "turno": {
                    "id": 1,
                    "nombre": "Mañana"
                },
                "modalidad": {
                    "id": 1,
                    "nombre": "Presencial"
                },
                "docenteGuia": {
                    "id": 1,
                    "name": "Juan Pérez"
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/v1/config-grupos?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/v1/config-grupos?page=1",
        "links": [],
        "next_page_url": null,
        "path": "http://localhost:8000/api/v1/config-grupos",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Config grupos obtenidos exitosamente"
}
```

### 2. Obtener Todos los Config Grupos (Sin paginación)
**GET** `/api/v1/config-grupos/getall`

**Descripción:** Obtiene todos los grupos de configuración sin paginación.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "periodo_lectivo_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Todos los config grupos obtenidos exitosamente"
}
```

### 3. Crear Config Grupo
**POST** `/api/v1/config-grupos`

**Descripción:** Crea un nuevo grupo de configuración.

**Permisos requeridos:** `config.grupos.crear`

**Cuerpo de la petición:**
```json
{
    "nombre": "A",
    "periodo_lectivo_id": 1,
    "grado_id": 1,
    "seccion_id": 1,
    "turno_id": 1,
    "modalidad_id": 1,
    "docente_guia": 1,
    "activo": true
}
```

**Validaciones:**
- `nombre`: requerido, string máximo 255 caracteres
- `periodo_lectivo_id`: requerido, debe existir en tabla conf_periodo_lectivos
- `grado_id`: requerido, debe existir en tabla config_grado
- `seccion_id`: requerido, debe existir en tabla config_seccion
- `turno_id`: requerido, debe existir en tabla config_turnos
- `modalidad_id`: requerido, debe existir en tabla config_modalidad
- `docente_guia`: opcional, debe existir en tabla users
- `activo`: opcional, boolean, default: true

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "A",
        "periodo_lectivo_id": 1,
        // ... resto de campos
    },
    "message": "Config grupo creado exitosamente"
}
```

### 4. Mostrar Config Grupo
**GET** `/api/v1/config-grupos/{id}`

**Descripción:** Obtiene un grupo de configuración específico por ID.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `id`: ID del config grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "A",
        "periodo_lectivo_id": 1,
        // ... resto de campos con relaciones
    },
    "message": "Config grupo obtenido exitosamente"
}
```

### 5. Actualizar Config Grupo
**PUT** `/api/v1/config-grupos/{id}`

**Descripción:** Actualiza un grupo de configuración existente.

**Permisos requeridos:** `config.grupos.editar`

**Parámetros de ruta:**
- `id`: ID del config grupo

**Cuerpo de la petición:** (mismos campos que crear, todos opcionales)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "A",
        "periodo_lectivo_id": 1,
        // ... campos actualizados
    },
    "message": "Config grupo actualizado exitosamente"
}
```

### 6. Eliminar Config Grupo (Soft Delete)
**DELETE** `/api/v1/config-grupos/{id}`

**Descripción:** Elimina lógicamente un grupo de configuración.

**Permisos requeridos:** `config.grupos.eliminar`

**Parámetros de ruta:**
- `id`: ID del config grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Config grupo eliminado exitosamente"
}
```

### 7. Grupos Filtrados por Múltiples Criterios
**GET** `/api/v1/config-grupos/filtered`

**Descripción:** Obtiene grupos aplicando múltiples filtros simultáneamente.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de consulta:**
- `periodo_id` (opcional): ID del período lectivo
- `grado_id` (opcional): ID del grado
- `turno_id` (opcional): ID del turno
- `seccion_id` (opcional): ID de la sección
- `modalidad_id` (opcional): ID de la modalidad
- `docente_guia` (opcional): ID del docente guía

**Ejemplo de uso:**
```
GET /api/v1/config-grupos/filtered?periodo_id=1&grado_id=2&turno_id=1
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 3,
            "nombre": "B",
            "grado_id": 2,
            "turno_id": 1,
            "periodo_lectivo_id": 1,
            "seccion_id": 1,
            "modalidad_id": 1,
            "docente_guia": 1,
            "activo": true,
            "periodo_lectivo": {
                "id": 1,
                "nombre": "2024-1"
            },
            "grado": {
                "id": 2,
                "nombre": "Segundo"
            },
            "seccion": {
                "id": 1,
                "nombre": "A"
            },
            "turno": {
                "id": 1,
                "nombre": "Mañana"
            },
            "modalidad": {
                "id": 1,
                "nombre": "Presencial"
            },
            "docenteGuia": {
                "id": 1,
                "name": "Juan Pérez"
            }
        }
    ],
    "message": "Grupos filtrados obtenidos exitosamente"
}
```

### 8. Grupos por Grado
**GET** `/api/v1/config-grupos/by-grado/{gradoId}`

**Descripción:** Obtiene grupos filtrados por grado específico.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `gradoId`: ID del grado

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "grado_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por grado obtenidos exitosamente"
}
```

### 9. Grupos por Sección
**GET** `/api/v1/config-grupos/by-seccion/{seccionId}`

**Descripción:** Obtiene grupos filtrados por sección específica.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `seccionId`: ID de la sección

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "seccion_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por sección obtenidos exitosamente"
}
```

### 10. Grupos por Turno
**GET** `/api/v1/config-grupos/by-turno/{turnoId}`

**Descripción:** Obtiene grupos filtrados por turno específico.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `turnoId`: ID del turno

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "turno_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por turno obtenidos exitosamente"
}
```

### 11. Grupos por Modalidad
**GET** `/api/v1/config-grupos/by-modalidad/{modalidadId}`

**Descripción:** Obtiene grupos filtrados por modalidad específica.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `modalidadId`: ID de la modalidad

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "modalidad_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por modalidad obtenidos exitosamente"
}
```

### 12. Grupos por Docente Guía
**GET** `/api/v1/config-grupos/by-docente-guia/{docenteId}`

**Descripción:** Obtiene grupos filtrados por docente guía específico.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `docenteId`: ID del docente guía

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "docente_guia": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por docente guía obtenidos exitosamente"
}
```

### 13. Grupos por Período Lectivo
**GET** `/api/v1/config-grupos/by-periodo-lectivo/{periodoId}`

**Descripción:** Obtiene grupos filtrados por período lectivo específico.

**Permisos requeridos:** `config.grupos.ver`

**Parámetros de ruta:**
- `periodoId`: ID del período lectivo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "periodo_lectivo_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Grupos por período lectivo obtenidos exitosamente"
}
```

---

## Endpoints para Controles Select

Los siguientes endpoints están diseñados específicamente para cargar datos en controles select del frontend.

### 14. Listar Grados
**GET** `/api/v1/config-grupos/grados/list`

**Descripción:** Obtiene una lista de todos los grados disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Primero"
        },
        {
            "id": 2,
            "nombre": "Segundo"
        }
    ],
    "message": "Grados obtenidos exitosamente"
}
```

### 15. Listar Secciones
**GET** `/api/v1/config-grupos/secciones/list`

**Descripción:** Obtiene una lista de todas las secciones disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A"
        },
        {
            "id": 2,
            "nombre": "B"
        }
    ],
    "message": "Secciones obtenidas exitosamente"
}
```

### 16. Listar Docentes Guía
**GET** `/api/v1/config-grupos/docentes-guia/list`

**Descripción:** Obtiene una lista de todos los docentes guía disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Juan Pérez"
        },
        {
            "id": 2,
            "name": "María García"
        }
    ],
    "message": "Docentes guía obtenidos exitosamente"
}
```

### 17. Listar Modalidades
**GET** `/api/v1/config-grupos/modalidades/list`

**Descripción:** Obtiene una lista de todas las modalidades disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Presencial"
        },
        {
            "id": 2,
            "nombre": "Virtual"
        }
    ],
    "message": "Modalidades obtenidas exitosamente"
}
```

### 18. Listar Turnos
**GET** `/api/v1/config-grupos/turnos/list`

**Descripción:** Obtiene una lista de todos los turnos disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Mañana"
        },
        {
            "id": 2,
            "nombre": "Tarde"
        }
    ],
    "message": "Turnos obtenidos exitosamente"
}
```

### 19. Listar Períodos Lectivos
**GET** `/api/v1/config-grupos/periodos-lectivos/list`

**Descripción:** Obtiene una lista de todos los períodos lectivos disponibles para controles select.

**Permisos requeridos:** `config.grupos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "2024-1"
        },
        {
            "id": 2,
            "nombre": "2024-2"
        }
    ],
    "message": "Períodos lectivos obtenidos exitosamente"
}
```

---

## Respuestas de Error

### 400 - Bad Request
```json
{
    "success": false,
    "message": "Parámetros de consulta inválidos"
}
```

### 401 - Unauthorized
```json
{
    "success": false,
    "message": "No autorizado"
}
```

### 403 - Forbidden
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción"
}
```

### 404 - Not Found
```json
{
    "success": false,
    "message": "Config grupo no encontrado"
}
```

### 422 - Unprocessable Entity
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "nombre": ["El campo nombre es obligatorio"],
        "periodo_lectivo_id": ["El campo periodo lectivo id es obligatorio"]
    }
}
```

### 500 - Internal Server Error
```json
{
    "success": false,
    "message": "Error interno del servidor"
}
```

---

## Middleware de Permisos

Todos los endpoints de config-grupos están protegidos por el middleware `check.permissions` con los siguientes permisos específicos:

### Permisos CRUD Principales:
- **`config.grupos.ver`** - Para endpoints de consulta y listado
- **`config.grupos.crear`** - Para crear nuevos grupos de configuración
- **`config.grupos.editar`** - Para actualizar grupos existentes
- **`config.grupos.eliminar`** - Para eliminar grupos

### Permisos para Controles Select:
Todos los endpoints de controles select utilizan el permiso **`config.grupos.ver`**, permitiendo que los usuarios con permisos de visualización de grupos puedan cargar las opciones necesarias para los formularios.

---

## Casos de Uso para Controles Select

### 1. Formulario de Configuración de Grupos
Los endpoints de controles select están diseñados para soportar formularios de configuración con selección en cascada:

1. **Seleccionar Período Lectivo** → `/periodos-lectivos/list`
2. **Seleccionar Grado** → `/grados/list`
3. **Seleccionar Sección** → `/secciones/list`
4. **Seleccionar Turno** → `/turnos/list`
5. **Seleccionar Modalidad** → `/modalidades/list`
6. **Seleccionar Docente Guía** → `/docentes-guia/list`

### 2. Filtros de Búsqueda
Los endpoints también pueden utilizarse para implementar filtros dinámicos en interfaces de búsqueda y reportes.

### 3. Validación de Datos
Antes de crear o actualizar registros de config-grupos, el frontend puede usar estos endpoints para validar que las opciones seleccionadas están disponibles y activas.

---

## Notas Importantes

1. **Relaciones:** Todos los endpoints incluyen las relaciones con las tablas relacionadas (periodo_lectivo, grado, seccion, turno, modalidad, docenteGuia).

2. **Soft Deletes:** Los registros eliminados se mantienen en la base de datos con `deleted_at` no nulo.

3. **Auditoría:** Todos los registros incluyen campos de auditoría (`created_by`, `updated_by`, `deleted_by`, `cambios`).

4. **Middleware:** Todas las rutas están protegidas por autenticación Sanctum y verificación de permisos específicos.

5. **Controles Select:** Los endpoints para controles select facilitan la implementación de formularios dinámicos con selección en cascada.

6. **Filtros Combinados:** El endpoint `/filtered` permite aplicar múltiples filtros simultáneamente para una búsqueda más precisa.

7. **Permisos Granulares:** Cada endpoint tiene permisos específicos que permiten un control de acceso detallado según el rol del usuario.

8. **Sincronización:** El sistema incluye soporte para sincronización offline con campos `is_synced`, `synced_at` y `version`.