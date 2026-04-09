# API ConfigGrupos

## Descripción
API para la gestión de grupos de configuración académica. Permite realizar operaciones CRUD sobre los grupos educativos que combinan grado, sección, turno y modalidad.

## Base URL
```
/api/v1/config-grupos
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo `config_grupos`:

| Endpoint | Método | Permiso Requerido |
|----------|--------|-------------------|
| `/` (listar) | GET | `config_grupos.index` |
| `/getall` (obtener todos) | GET | `config_grupos.index` |
| `/` (crear) | POST | `config_grupos.create` |
| `/{id}` (mostrar) | GET | `config_grupos.show` |
| `/{id}` (actualizar) | PUT | `config_grupos.update` |
| `/{id}` (eliminar) | DELETE | `config_grupos.delete` |
| `/by-grado/{gradoId}` | GET | `config_grupos.filter` |
| `/by-seccion/{seccionId}` | GET | `config_grupos.filter` |
| `/by-turno/{turnoId}` | GET | `config_grupos.filter` |
| `/by-modalidad/{modalidadId}` | GET | `config_grupos.filter` |
| `/by-docente-guia/{docenteId}` | GET | `config_grupos.filter` |
| `/by-periodo-lectivo/{periodoLectivoId}` | GET | `config_grupos.filter` |
| `/opciones/grados` | GET | `config_grupos.index` |
| `/opciones/secciones` | GET | `config_grupos.index` |
| `/opciones/docentes-guia` | GET | `config_grupos.index` |
| `/opciones/modalidades` | GET | `config_grupos.index` |
| `/opciones/turnos` | GET | `config_grupos.index` |
| `/opciones/periodos-lectivos` | GET | `config_grupos.index` |
| `/not-synced` | GET | `config_grupos.sync` |
| `/mark-synced/{uuid}` | POST | `config_grupos.sync` |
| `/updated-after/{date}` | GET | `config_grupos.sync` |

**Nota:** Los permisos están organizados bajo la categoría `configuracion_academica` en el sistema de permisos.

## Endpoints

### 1. Listar grupos (paginado)
**GET** `/api/v1/config-grupos`

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
                "grado_id": 1,
                "seccion_id": 1,
                "turno_id": 1,
                "modalidad_id": 1,
                "docente_guia": 5,
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
                "updated_at": "2024-01-15T10:30:00Z",
                "grado": {
                    "id": 1,
                    "nombre": "Primer Grado",
                    "abreviatura": "1°"
                },
                "seccion": {
                    "id": 1,
                    "nombre": "Sección A"
                },
                "turno": {
                    "id": 1,
                    "nombre": "Mañana"
                },
                "modalidad": {
                    "id": 1,
                    "nombre": "Presencial"
                },
                "docente": {
                    "id": 5,
                    "name": "María García",
                    "email": "maria.garcia@example.com"
                }
            }
        ],
        "per_page": 15,
        "total": 1
    },
    "message": "Grupos obtenidos exitosamente"
}
```

### 2. Obtener todos los grupos (sin paginación)
**GET** `/api/v1/config-grupos/getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "grado_id": 1,
            "seccion_id": 1,
            "turno_id": 1,
            "modalidad_id": 1,
            "docente_guia": 5,
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
            "updated_at": "2024-01-15T10:30:00Z",
            "grado": {
                "id": 1,
                "nombre": "Primer Grado",
                "abreviatura": "1°"
            },
            "seccion": {
                "id": 1,
                "nombre": "Sección A"
            },
            "turno": {
                "id": 1,
                "nombre": "Mañana"
            },
            "modalidad": {
                "id": 1,
                "nombre": "Presencial"
            },
            "docente": {
                "id": 5,
                "name": "María García",
                "email": "maria.garcia@example.com"
            }
        }
    ],
    "message": "Grupos obtenidos exitosamente"
}
```

### 3. Crear nuevo grupo
**POST** `/api/v1/config-grupos`

**Cuerpo de la petición:**
```json
{
    "grado_id": 2,
    "seccion_id": 1,
    "turno_id": 1,
    "modalidad_id": 1,
    "docente_guia": 6
}
```

**Validaciones:**
- `grado_id`: requerido, entero, debe existir en config_grados
- `seccion_id`: requerido, entero, debe existir en config_secciones
- `turno_id`: requerido, entero, debe existir en config_turnos
- `modalidad_id`: requerido, entero, debe existir en config_modalidades
- `docente_guia`: opcional, entero, debe existir en users
- La combinación de grado_id, seccion_id, turno_id y modalidad_id debe ser única

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "grado_id": 2,
        "seccion_id": 1,
        "turno_id": 1,
        "modalidad_id": 1,
        "docente_guia": 6,
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
    "message": "Grupo creado exitosamente"
}
```

### 4. Obtener grupo específico
**GET** `/api/v1/config-grupos/{id}`

**Parámetros de ruta:**
- `id`: ID del grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "grado_id": 1,
        "seccion_id": 1,
        "turno_id": 1,
        "modalidad_id": 1,
        "docente_guia": 5,
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
        "updated_at": "2024-01-15T10:30:00Z",
        "grado": {
            "id": 1,
            "nombre": "Primer Grado",
            "abreviatura": "1°"
        },
        "seccion": {
            "id": 1,
            "nombre": "Sección A"
        },
        "turno": {
            "id": 1,
            "nombre": "Mañana"
        },
        "modalidad": {
            "id": 1,
            "nombre": "Presencial"
        },
        "docente": {
            "id": 5,
            "name": "María García",
            "email": "maria.garcia@example.com"
        }
    },
    "message": "Grupo obtenido exitosamente"
}
```

### 5. Actualizar grupo
**PUT** `/api/v1/config-grupos/{id}`

**Parámetros de ruta:**
- `id`: ID del grupo

**Cuerpo de la petición:**
```json
{
    "grado_id": 1,
    "seccion_id": 2,
    "turno_id": 1,
    "modalidad_id": 1,
    "docente_guia": 7
}
```

**Validaciones:**
- `grado_id`: requerido, entero, debe existir en config_grados
- `seccion_id`: requerido, entero, debe existir en config_secciones
- `turno_id`: requerido, entero, debe existir en config_turnos
- `modalidad_id`: requerido, entero, debe existir en config_modalidades
- `docente_guia`: opcional, entero, debe existir en users
- La combinación de grado_id, seccion_id, turno_id y modalidad_id debe ser única (excepto el registro actual)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "grado_id": 1,
        "seccion_id": 2,
        "turno_id": 1,
        "modalidad_id": 1,
        "docente_guia": 7,
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
                    "seccion_id": {
                        "anterior": 1,
                        "nuevo": 2
                    },
                    "docente_guia": {
                        "anterior": 5,
                        "nuevo": 7
                    }
                }
            }
        ],
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T12:00:00Z"
    },
    "message": "Grupo actualizado exitosamente"
}
```

### 6. Eliminar grupo
**DELETE** `/api/v1/config-grupos/{id}`

**Parámetros de ruta:**
- `id`: ID del grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Grupo eliminado exitosamente"
}
```

## Endpoints de Filtros

### 7. Obtener grupos por grado
**GET** `/api/v1/config-grupos/by-grado/{gradoId}`

**Parámetros de ruta:**
- `gradoId`: ID del grado

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "grado_id": 1,
            "seccion_id": 1,
            "turno_id": 1,
            "modalidad_id": 1,
            "docente_guia": 5,
            "grado": {
                "id": 1,
                "nombre": "Primer Grado",
                "abreviatura": "1°"
            },
            "seccion": {
                "id": 1,
                "nombre": "Sección A"
            },
            "turno": {
                "id": 1,
                "nombre": "Mañana"
            },
            "modalidad": {
                "id": 1,
                "nombre": "Presencial"
            }
        }
    ],
    "message": "Grupos por grado obtenidos exitosamente"
}
```

### 8. Obtener grupos por sección
**GET** `/api/v1/config-grupos/by-seccion/{seccionId}`

### 9. Obtener grupos por turno
**GET** `/api/v1/config-grupos/by-turno/{turnoId}`

### 10. Obtener grupos por modalidad
**GET** `/api/v1/config-grupos/by-modalidad/{modalidadId}`

### 11. Obtener grupos por docente guía
**GET** `/api/v1/config-grupos/by-docente-guia/{docenteId}`

### 12. Obtener grupos por período lectivo
**GET** `/api/v1/config-grupos/by-periodo-lectivo/{periodoLectivoId}`

**Descripción:** Obtiene todos los grupos asociados a un período lectivo específico.

**Parámetros de ruta:**
- `periodoLectivoId` (integer, requerido): ID del período lectivo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "grado_id": 1,
            "seccion_id": 1,
            "turno_id": 1,
            "modalidad_id": 1,
            "periodo_lectivo_id": 1,
            "docente_guia": 5,
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "cambios": [...],
            "is_synced": true,
            "synced_at": "2024-01-15T10:30:00.000000Z",
            "updated_locally_at": null,
            "version": 1,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "deleted_at": null,
            "grado": {
                "id": 1,
                "nombre": "Primer Grado",
                "abreviatura": "1°"
            },
            "seccion": {
                "id": 1,
                "nombre": "Sección A"
            },
            "turno": {
                "id": 1,
                "nombre": "Mañana"
            },
            "modalidad": {
                "id": 1,
                "nombre": "Presencial"
            },
            "periodo_lectivo": {
                "id": 1,
                "nombre": "2024"
            },
            "docente_guia_info": {
                "id": 5,
                "name": "María García"
            }
        }
    ],
    "message": "Grupos por período lectivo obtenidos exitosamente"
}
```

**Respuesta de error (500):**
```json
{
    "success": false,
    "message": "Error al obtener grupos por período lectivo: [mensaje de error]",
    "errors": []
}
```

## Endpoints de Opciones

### 13. Obtener lista de grados disponibles
**GET** `/api/v1/config-grupos/opciones/grados`

**Descripción:** Obtiene la lista de todos los grados disponibles para la creación de grupos.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Primer Grado",
            "abreviatura": "1°"
        },
        {
            "id": 2,
            "nombre": "Segundo Grado", 
            "abreviatura": "2°"
        },
        {
            "id": 3,
            "nombre": "Tercer Grado",
            "abreviatura": "3°"
        }
    ],
    "message": "Grados obtenidos exitosamente"
}
```

### 14. Obtener lista de secciones disponibles
**GET** `/api/v1/config-grupos/opciones/secciones`

**Descripción:** Obtiene la lista de todas las secciones disponibles para la creación de grupos.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Sección A"
        },
        {
            "id": 2,
            "nombre": "Sección B"
        },
        {
            "id": 3,
            "nombre": "Sección C"
        }
    ],
    "message": "Secciones obtenidas exitosamente"
}
```

### 15. Obtener lista de docentes guía disponibles
**GET** `/api/v1/config-grupos/opciones/docentes-guia`

**Descripción:** Obtiene la lista de todos los usuarios con rol 'docente' disponibles para asignar como docente guía.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "María García",
            "email": "maria.garcia@example.com"
        },
        {
            "id": 6,
            "name": "Carlos López",
            "email": "carlos.lopez@example.com"
        },
        {
            "id": 7,
            "name": "Ana Martínez",
            "email": "ana.martinez@example.com"
        }
    ],
    "message": "Docentes guía obtenidos exitosamente"
}
```

### 16. Obtener lista de modalidades disponibles
**GET** `/api/v1/config-grupos/opciones/modalidades`

**Descripción:** Obtiene la lista de todas las modalidades disponibles para la creación de grupos.

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
        },
        {
            "id": 3,
            "nombre": "Semipresencial"
        }
    ],
    "message": "Modalidades obtenidas exitosamente"
}
```

### 17. Obtener lista de turnos disponibles
**GET** `/api/v1/config-grupos/opciones/turnos`

**Descripción:** Obtiene la lista de todos los turnos disponibles para la creación de grupos.

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
        },
        {
            "id": 3,
            "nombre": "Noche"
        }
    ],
    "message": "Turnos obtenidos exitosamente"
}
```

### 18. Obtener lista de períodos lectivos disponibles
**GET** `/api/v1/config-grupos/opciones/periodos-lectivos`

**Descripción:** Obtiene la lista de todos los períodos lectivos disponibles para la creación de grupos.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Período 2024-2025"
        },
        {
            "id": 2,
            "nombre": "Período 2025-2026"
        },
        {
            "id": 3,
            "nombre": "Período 2026-2027"
        }
    ],
    "message": "Períodos lectivos obtenidos exitosamente"
}
```

**Respuesta de error (500):**
```json
{
    "success": false,
    "message": "Error al obtener los datos: [mensaje de error específico]",
    "errors": []
}
```

## Endpoints de Sincronización (Modo Offline)

### 19. Obtener registros no sincronizados
**GET** `/api/v1/config-grupos/sync/unsynced`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "grado_id": 2,
            "seccion_id": 1,
            "turno_id": 1,
            "modalidad_id": 1,
            "docente_guia": 6,
            "is_synced": false,
            "synced_at": null,
            "updated_locally_at": "2024-01-15T11:00:00Z",
            "version": 1
        }
    ],
    "message": "Registros no sincronizados obtenidos exitosamente"
}
```

### 20. Marcar registro como sincronizado
**PATCH** `/api/v1/config-grupos/sync/{id}/mark-synced`

**Parámetros de ruta:**
- `id`: ID del grupo

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

### 20. Obtener registros actualizados después de una fecha
**GET** `/api/v1/config-grupos/sync/updated-after?updated_after=2024-01-15T10:00:00Z`

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
            "grado_id": 1,
            "seccion_id": 2,
            "turno_id": 1,
            "modalidad_id": 1,
            "docente_guia": 7,
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
    "message": "Grupo no encontrado",
    "errors": []
}
```

### 422 - Validation Error
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "grado_id": [
            "El campo grado es obligatorio.",
            "El grado seleccionado no existe."
        ],
        "seccion_id": [
            "El campo sección es obligatorio.",
            "La sección seleccionada no existe."
        ],
        "turno_id": [
            "El campo turno es obligatorio.",
            "El turno seleccionado no existe."
        ],
        "modalidad_id": [
            "El campo modalidad es obligatorio.",
            "La modalidad seleccionada no existe."
        ],
        "docente_guia": [
            "El docente guía seleccionado no existe."
        ],
        "combinacion_unica": [
            "Ya existe un grupo con esta combinación de grado, sección, turno y modalidad."
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
7. **Relaciones**: Los grupos incluyen relaciones con grado, sección, turno, modalidad y docente guía.
8. **Unicidad**: La combinación de grado, sección, turno y modalidad debe ser única.
9. **Filtros**: Se proporcionan endpoints específicos para filtrar grupos por cada componente.
10. **Docente Guía**: Campo opcional que permite asignar un docente responsable del grupo.