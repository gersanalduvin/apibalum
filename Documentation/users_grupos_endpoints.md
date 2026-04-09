# API Documentation - Users Grupos

## Descripción
API para gestionar la matrícula de usuarios en grupos académicos, incluyendo información de período lectivo, grado, grupo, turno y estadísticas académicas.

## Base URL
```
/api/v1/users-grupos
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Endpoints

### 1. Listar Users Grupos (Paginado)
**GET** `/api/v1/users-grupos`

**Descripción:** Obtiene una lista paginada de users grupos filtrados por user_id.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de consulta:**
- `user_id` (requerido): ID del usuario para filtrar los registros
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
                "user_id": 1,
                "fecha_matricula": "2024-01-15",
                "periodo_lectivo_id": 1,
                "grado_id": 1,
                "grupo_id": 1,
                "turno_id": 1,
                "numero_recibo": "REC-001",
                "tipo_ingreso": "nuevo_ingreso",
                "estado": "activo",
                "activar_estadistica": true,
                "corte_retiro": null,
                "corte_ingreso": "corte1",
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
                "grupo": {
                    "id": 1,
                    "nombre": "A"
                },
                "turno": {
                    "id": 1,
                    "nombre": "Mañana"
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/v1/users-grupos?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/v1/users-grupos?page=1",
        "links": [],
        "next_page_url": null,
        "path": "http://localhost:8000/api/v1/users-grupos",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Users grupos obtenidos exitosamente"
}
```

**Respuesta de error (400):**
```json
{
    "success": false,
    "message": "El parámetro user_id es requerido"
}
```

### 2. Obtener Todos los Users Grupos (Sin paginación)
**GET** `/api/v1/users-grupos/getall`

**Descripción:** Obtiene todos los users grupos de un usuario específico sin paginación.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de consulta:**
- `user_id` (requerido): ID del usuario para filtrar los registros

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "fecha_matricula": "2024-01-15",
            // ... resto de campos
        }
    ],
    "message": "Todos los users grupos obtenidos exitosamente"
}
```

**Respuesta de error (400):**
```json
{
    "success": false,
    "message": "El parámetro user_id es requerido"
}
```

### 3. Crear User Grupo
**POST** `/api/v1/users-grupos`

**Descripción:** Crea un nuevo registro de user grupo.

**Permisos requeridos:** `usuarios.alumnos.matricular`

**Cuerpo de la petición:**
```json
{
    "user_id": 1,
    "fecha_matricula": "2024-01-15",
    "periodo_lectivo_id": 1,
    "grado_id": 1,
    "grupo_id": 1,
    "turno_id": 1,
    "numero_recibo": "REC-001",
    "tipo_ingreso": "nuevo_ingreso",
    "estado": "activo",
    "activar_estadistica": true,
    "corte_ingreso": "corte1"
}
```

**Validaciones:**
- `user_id`: requerido, debe existir en tabla users
- `fecha_matricula`: requerido, formato fecha válido
- `periodo_lectivo_id`: requerido, debe existir en tabla conf_periodo_lectivos
- `grado_id`: requerido, debe existir en tabla config_grado
- `grupo_id`: opcional, debe existir en tabla config_grupos si se proporciona
- `turno_id`: requerido, debe existir en tabla config_turnos
- `numero_recibo`: opcional, string máximo 255 caracteres
- `tipo_ingreso`: requerido, enum (reingreso, nuevo_ingreso)
- `estado`: opcional, enum (activo, no_activo, retiro_anticipado), default: activo
- `activar_estadistica`: opcional, boolean, default: false
- `corte_retiro`: opcional, enum (corte1, corte2, corte3, corte4), solo si activar_estadistica es true
- `corte_ingreso`: opcional, enum (corte1, corte2, corte3, corte4), solo si activar_estadistica es true

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "fecha_matricula": "2024-01-15",
        // ... resto de campos
    },
    "message": "User grupo creado exitosamente"
}
```

### 4. Mostrar User Grupo
**GET** `/api/v1/users-grupos/{id}`

**Descripción:** Obtiene un user grupo específico por ID.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de ruta:**
- `id`: ID del user grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "fecha_matricula": "2024-01-15",
        // ... resto de campos con relaciones
    },
    "message": "User grupo obtenido exitosamente"
}
```

### 5. Actualizar User Grupo
**PUT** `/api/v1/users-grupos/{id}`

**Descripción:** Actualiza un user grupo existente.

**Permisos requeridos:** `usuarios.alumnos.matricular`

**Parámetros de ruta:**
- `id`: ID del user grupo

**Cuerpo de la petición:** (mismos campos que crear, todos opcionales)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "fecha_matricula": "2024-01-15",
        // ... campos actualizados
    },
    "message": "User grupo actualizado exitosamente"
}
```

### 6. Eliminar User Grupo (Soft Delete)
**DELETE** `/api/v1/users-grupos/{id}`

**Descripción:** Elimina lógicamente un user grupo.

**Permisos requeridos:** `usuarios.alumnos.matricular`

**Parámetros de ruta:**
- `id`: ID del user grupo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "User grupo eliminado exitosamente"
}
```

### 7. Restaurar User Grupo
**POST** `/api/v1/users-grupos/{id}/restore`

**Descripción:** Restaura un user grupo eliminado lógicamente.

**Permisos requeridos:** `usuarios.alumnos.crear`

**Parámetros de ruta:**
- `id`: ID del user grupo eliminado

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        // ... campos restaurados
    },
    "message": "User grupo restaurado exitosamente"
}
```

### 8. Generar PDF de Ficha de Inscripción
**GET** `/api/v1/users-grupos/{id}/ficha-inscripcion-pdf`

**Descripción:** Genera un PDF con la ficha de inscripción del alumno basado en el registro de UsersGrupo.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de ruta:**
- `id`: ID del registro de UsersGrupo

**Respuesta exitosa (200):**
- **Content-Type:** `application/pdf`
- **Content-Disposition:** `attachment; filename="ficha_inscripcion_[nombre_alumno].pdf"`
- **Cuerpo:** Archivo PDF binario

**Estructura del PDF:**
El PDF incluye las siguientes secciones:

1. **Datos Básicos del Alumno:**
   - Nombres y apellidos completos
   - Número ROC (código MINED)
   - Fecha de inscripción (fecha_matricula del UsersGrupo)
   - Nivel, grado, grupo, sección y turno (desde relaciones de UsersGrupo)
   - Período lectivo
   - Edad, código estudiantil, sexo
   - Lugar de nacimiento, nacionalidad
   - Dirección, teléfono, email

2. **Información Familiar:**
   - Datos de la madre (nombre, apellidos, cédula, fecha nacimiento, etc.)
   - Datos del padre (nombre, apellidos, cédula, fecha nacimiento, etc.)
   - Datos del responsable (si aplica)
   - Información de familiares adicionales

3. **Contacto de Emergencia:**
   - Nombre, teléfono, relación con el alumno

4. **Área de Desarrollo del Niño:**
   - Información sobre desarrollo infantil

5. **Área Social:**
   - Datos sociales del alumno

6. **Área Comunicativa:**
   - Información sobre comunicación

7. **Área Psicológica:**
   - Datos psicológicos relevantes

8. **Área Médica:**
   - Información médica del alumno

9. **Área de Firma:**
   - Espacio para firmas y sellos

**Características del PDF:**
- Formato: Carta (Letter)
- Orientación: Vertical (Portrait)
- Márgenes: 10mm en todos los lados
- Codificación: UTF-8
- Vista utilizada: `pdf.ficha-inscripcion`

**Respuesta de error (404):**
```json
{
    "success": false,
    "message": "Registro de alumno en grupo no encontrado"
}
```

**Respuesta de error (400):**
```json
{
    "success": false,
    "message": "El usuario no es un alumno"
}
```

**Respuesta de error (500):**
```json
{
    "success": false,
    "message": "Error interno del servidor al generar el PDF"
}
```

**Notas importantes:**
- El endpoint utiliza el ID de UsersGrupo para acceder a los datos del alumno a través de la relación `user`
- Los datos de grado, grupo, turno y período lectivo se obtienen directamente de las relaciones de UsersGrupo
- La fecha de inscripción se toma del campo `fecha_matricula` de UsersGrupo
- Los campos booleanos se formatean como 'Sí' o 'No' en el PDF
- Se requiere que el usuario relacionado sea de tipo 'alumno'

### 9. Users Grupos por Usuario
**GET** `/api/v1/users-grupos/user/{userId}`

**Descripción:** Obtiene todos los grupos de un usuario específico.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de ruta:**
- `userId`: ID del usuario

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Users grupos por usuario obtenidos exitosamente"
}
```

### 9. Users Grupos por Período
**GET** `/api/v1/users-grupos/periodo/{periodoId}`

**Descripción:** Obtiene todos los grupos de un período lectivo específico.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de ruta:**
- `periodoId`: ID del período lectivo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "periodo_lectivo_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Users grupos por período obtenidos exitosamente"
}
```

### 10. Users Grupos Activos
**GET** `/api/v1/users-grupos/activos/list`

**Descripción:** Obtiene todos los users grupos con estado activo.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "estado": "activo",
            // ... resto de campos
        }
    ],
    "message": "Users grupos activos obtenidos exitosamente"
}
```

### 11. Users Grupos con Estadística
**GET** `/api/v1/users-grupos/estadistica/list`

**Descripción:** Obtiene todos los users grupos que tienen estadísticas activadas.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "activar_estadistica": true,
            // ... resto de campos
        }
    ],
    "message": "Users grupos con estadística obtenidos exitosamente"
}
```

### 12. Buscar por Grado y Período
**GET** `/api/v1/users-grupos/grado-periodo/search`

**Descripción:** Busca users grupos por grado y período lectivo.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de consulta:**
- `grado_id`: ID del grado (requerido)
- `periodo_lectivo_id`: ID del período lectivo (requerido)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "grado_id": 1,
            "periodo_lectivo_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Users grupos por grado y período obtenidos exitosamente"
}
```

### 13. Estadísticas de Users Grupos
**GET** `/api/v1/users-grupos/estadisticas/resumen`

**Descripción:** Obtiene estadísticas resumidas de users grupos por estado.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "activo": 150,
        "no_activo": 25,
        "retiro_anticipado": 10,
        "total": 185
    },
    "message": "Estadísticas de users grupos obtenidas exitosamente"
}
```

### 14. Cambiar Estado
**PATCH** `/api/v1/users-grupos/{id}/cambiar-estado`

**Descripción:** Cambia el estado de un user grupo específico.

### 15. Enviar Ficha de Inscripción por Email (Adjunto)
**POST** `/api/v1/users-grupos/{id}/ficha-inscripcion-email`

**Descripción:** Encola el envío de la ficha de inscripción del alumno por correo electrónico, adjuntando el PDF generado directamente (sin subir a S3). El envío se realiza de forma asíncrona mediante colas (Redis) y Amazon SES.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de ruta:**
- `id`: ID del registro de `UsersGrupo` (matrícula del alumno)

**Cuerpo de la petición (JSON):**
```json
{
  "from": "remitente@dominio.com", // opcional
  "cc": "copia@dominio.com",      // opcional
  "bcc": "oculta@dominio.com",    // opcional
  "delay": 10                       // opcional (segundos)
}
```

**Validaciones:**
- `from`: opcional, email válido
- `cc`: opcional, email válido (actualmente un único email)
- `bcc`: opcional, email válido (actualmente un único email)
- `delay`: opcional, entero ≥ 0 (segundos para retrasar la ejecución del job)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "job_id": "uuid-del-job",
    "queue": "emails"
  },
  "message": "Envío de ficha encolado exitosamente"
}
```

**Proceso interno:**
- Se genera el PDF de la ficha usando la vista correspondiente (`pdf.ficha-inscripcion` o `pdf.ficha-inscripcion-reingreso`) y `SnappyPdf`.
- Se obtiene automáticamente el destinatario desde `users_grupos.user.correo_notificaciones`.
- El PDF se adjunta directamente al correo electrónico utilizando `SES SendRawEmail`.
- No se sube el archivo a S3 ni se generan enlaces pre-firmados.
- El job se asigna a la cola `emails` y soporta reintentos.

**Notas:**
- Tamaño máximo del correo raw en SES: 10 MB (incluye cuerpo y adjuntos).
- Para múltiples destinatarios en `cc`/`bcc`, se recomienda extender el endpoint para aceptar listas; la implementación actual valida un único email.

**Permisos requeridos:** `usuarios.alumnos.matricular`

**Parámetros de ruta:**
- `id`: ID del user grupo

**Cuerpo de la petición:**
```json
{
    "estado": "no_activo"
}
```

**Validaciones:**
- `estado`: requerido, enum (activo, no_activo, retiro_anticipado)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "estado": "no_activo",
        // ... resto de campos
    },
    "message": "Estado del user grupo cambiado exitosamente"
}
```

## Códigos de Error

### 400 - Bad Request
```json
{
    "success": false,
    "message": "Datos inválidos",
    "errors": {}
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
    "message": "User grupo no encontrado"
}
```

### 422 - Validation Error
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "user_id": ["El campo user id es obligatorio"],
        "fecha_matricula": ["El campo fecha matricula es obligatorio"]
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

## Notas Importantes

1. **Relaciones:** Todos los endpoints incluyen las relaciones con las tablas relacionadas (user, periodo_lectivo, grado, grupo, turno).

2. **Validación de Estadísticas:** Los campos `corte_retiro` y `corte_ingreso` solo son válidos cuando `activar_estadistica` es `true`.

3. **Unicidad:** Un usuario no puede tener más de una matrícula activa en el mismo período lectivo.

4. **Soft Deletes:** Los registros eliminados se mantienen en la base de datos con `deleted_at` no nulo.

5. **Auditoría:** Todos los registros incluyen campos de auditoría (`created_by`, `updated_by`, `deleted_by`, `cambios`).

513| 6. **Middleware:** Todas las rutas están protegidas por autenticación Sanctum y verificación de permisos específicos.

---

## Endpoints para Controles Select

Los siguientes endpoints están diseñados específicamente para cargar datos en controles select del frontend, proporcionando listas de opciones para formularios de matrícula.

### 14. Listar Períodos Lectivos
**GET** `/api/v1/users-grupos/periodos-lectivos/list`

**Descripción:** Obtiene una lista de todos los períodos lectivos disponibles para controles select.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "2024-1",
            "fecha_inicio": "2024-01-15",
            "fecha_fin": "2024-06-30",
            "activo": true
        },
        {
            "id": 2,
            "nombre": "2024-2",
            "fecha_inicio": "2024-07-01",
            "fecha_fin": "2024-12-15",
            "activo": false
        }
    ],
    "message": "Períodos lectivos obtenidos exitosamente"
}
```

### 15. Listar Grados
**GET** `/api/v1/users-grupos/grados/list`

**Descripción:** Obtiene una lista de todos los grados disponibles para controles select.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Primero",
            "nivel": "primaria",
            "orden": 1,
            "activo": true
        },
        {
            "id": 2,
            "nombre": "Segundo",
            "nivel": "primaria",
            "orden": 2,
            "activo": true
        }
    ],
    "message": "Grados obtenidos exitosamente"
}
```

### 16. Listar Grupos
**GET** `/api/v1/users-grupos/grupos/list`

**Descripción:** Obtiene una lista de todos los grupos disponibles para controles select.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de consulta opcionales:**
- `periodo` (opcional): ID del período lectivo para filtrar grupos
- `grado` (opcional): ID del grado para filtrar grupos  
- `turno` (opcional): ID del turno para filtrar grupos

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "A",
            "grado_id": 1,
            "turno_id": 1,
            "periodo_lectivo_id": 1,
            "capacidad_maxima": 30,
            "activo": true,
            "grado": {
                "id": 1,
                "nombre": "Primero"
            },
            "turno": {
                "id": 1,
                "nombre": "Mañana"
            }
        }
    ],
    "message": "Grupos obtenidos exitosamente"
}
```

### 17. Grupos por Período Lectivo
**GET** `/api/v1/users-grupos/grupos/by-periodo/{periodoId}`

**Descripción:** Obtiene grupos filtrados por período lectivo específico.

**Permisos requeridos:** `usuarios.alumnos.ver`

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
    "message": "Grupos por período obtenidos exitosamente"
}
```

### 18. Grupos por Grado
**GET** `/api/v1/users-grupos/grupos/by-grado/{gradoId}`

**Descripción:** Obtiene grupos filtrados por grado específico.

**Permisos requeridos:** `usuarios.alumnos.ver`

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

### 19. Grupos por Turno
**GET** `/api/v1/users-grupos/grupos/by-turno/{turnoId}`

**Descripción:** Obtiene grupos filtrados por turno específico.

**Permisos requeridos:** `usuarios.alumnos.ver`

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

### 20. Grupos Filtrados (Múltiples criterios)
**GET** `/api/v1/users-grupos/grupos/filtered`

**Descripción:** Obtiene grupos aplicando múltiples filtros simultáneamente.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Parámetros de consulta:**
- `periodo` (opcional): ID del período lectivo
- `grado` (opcional): ID del grado
- `turno` (opcional): ID del turno

**Ejemplo de uso:**
```
GET /api/v1/users-grupos/grupos/filtered?periodo=1&grado=2&turno=1
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
            // ... resto de campos con relaciones
        }
    ],
    "message": "Grupos filtrados obtenidos exitosamente"
}
```

### 21. Listar Turnos
**GET** `/api/v1/users-grupos/turnos/list`

**Descripción:** Obtiene una lista de todos los turnos disponibles para controles select.

**Permisos requeridos:** `usuarios.alumnos.ver`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Mañana",
            "hora_inicio": "07:00:00",
            "hora_fin": "12:00:00",
            "activo": true
        },
        {
            "id": 2,
            "nombre": "Tarde",
            "hora_inicio": "13:00:00",
            "hora_fin": "18:00:00",
            "activo": true
        }
    ],
    "message": "Turnos obtenidos exitosamente"
}
```

---

## Middleware de Permisos

Todos los endpoints de users_grupos están protegidos por el middleware `check.permissions` con los siguientes permisos específicos:

### Permisos CRUD Principales:
- **`usuarios.alumnos.ver`** - Para endpoints de consulta y listado
- **`usuarios.alumnos.crear`** - Para crear nuevos registros de matrícula
- **`usuarios.alumnos.editar`** - Para actualizar registros existentes
- **`usuarios.alumnos.eliminar`** - Para eliminar registros
- **`usuarios.alumnos.matricular`** - Para operaciones específicas de matrícula

### Permisos para Controles Select:
Todos los endpoints de controles select (períodos lectivos, grados, grupos y turnos) utilizan el permiso **`usuarios.alumnos.ver`**, permitiendo que los usuarios con permisos de visualización de alumnos puedan cargar las opciones necesarias para los formularios.

### Aplicación del Middleware:
```php
// Ejemplo de aplicación en las rutas
Route::middleware(['auth:sanctum', 'check.permissions:usuarios.alumnos.ver'])
    ->get('/periodos-lectivos/list', [ConfPeriodoLectivoController::class, 'index']);
```

---

## Casos de Uso para Controles Select

### 1. Formulario de Matrícula
Los endpoints de controles select están diseñados para soportar formularios de matrícula con selección en cascada:

1. **Seleccionar Período Lectivo** → `/periodos-lectivos/list`
2. **Seleccionar Grado** → `/grados/list`
3. **Seleccionar Turno** → `/turnos/list`
4. **Seleccionar Grupo** → `/grupos/filtered?periodo={id}&grado={id}&turno={id}`

### 2. Filtros de Búsqueda
Los endpoints también pueden utilizarse para implementar filtros dinámicos en interfaces de búsqueda y reportes.

### 3. Validación de Datos
Antes de crear o actualizar registros de users_grupos, el frontend puede usar estos endpoints para validar que las opciones seleccionadas están disponibles y activas.

---

## Notas Importantes Actualizadas

1. **Relaciones:** Todos los endpoints incluyen las relaciones con las tablas relacionadas (user, periodo_lectivo, grado, grupo, turno).

2. **Validación de Estadísticas:** Los campos `corte_retiro` y `corte_ingreso` solo son válidos cuando `activar_estadistica` es `true`.

3. **Unicidad:** Un usuario no puede tener más de una matrícula activa en el mismo período lectivo.

4. **Soft Deletes:** Los registros eliminados se mantienen en la base de datos con `deleted_at` no nulo.

5. **Auditoría:** Todos los registros incluyen campos de auditoría (`created_by`, `updated_by`, `deleted_by`, `cambios`).

6. **Middleware:** Todas las rutas están protegidas por autenticación Sanctum y verificación de permisos específicos.

7. **Controles Select:** Los nuevos endpoints para controles select facilitan la implementación de formularios dinámicos con selección en cascada.

8. **Filtros Combinados:** El endpoint `/grupos/filtered` permite aplicar múltiples filtros simultáneamente para una búsqueda más precisa.

9. **Permisos Granulares:** Cada endpoint tiene permisos específicos que permiten un control de acceso detallado según el rol del usuario.