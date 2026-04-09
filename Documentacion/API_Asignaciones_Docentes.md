# API Asignaciones Docentes

## Descripción
Gestiona la asignación de asignaturas por grado a docentes por grupo y permite consultar todas las asignaturas no asignadas a ningún docente. Todas las respuestas son JSON.

- Autenticación: `auth:sanctum`
- Permiso requerido en todos los endpoints: `usuarios.docentes.asignar_materias`
- Auditoría: habilitada (trait Auditable); modelo registrado en `AuditController`

## Rutas Base
- Prefijo: `/api/v1/usuarios/docentes`

## Endpoints

### 1. Listar asignaturas NO asignadas (global)
- Método: `GET`
- URL: `/api/v1/usuarios/docentes/asignaciones/no-asignadas`
- Parámetros: ninguno
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "periodo_lectivo_id": 10,
      "grado_id": 3,
      "materia_id": 55,
      "grupo_id": 77,
      "periodo_lectivo": "2025",
      "GRUPO": "1-A (VESPERTINO)",
      "asignatura": "FISICA"
    }
  ],
  "message": "Asignaturas no asignadas"
}
``` 
- Ordenado por grupo y asignatura.
- Errores
  - 401: usuario no autenticado
  - 403: sin permiso

### 2. Listar asignaciones de un docente
- Método: `GET`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones`
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 12,
      "asignatura_grado_id": 123,
      "grupo_id": 77,
      "asignatura": "FISICA",
      "grupo": "1 GRADO A (VESPERTINO)"
    }
  ],
  "message": "Asignaciones del docente"
}
```

- Ordenado por grupo y asignatura.

### 3. Crear asignación
- Método: `POST`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones`
- Body (JSON):
```json
{
  "user_id": 12,
  "asignatura_grado_id": 123,
  "grupo_id": 77,
  "permiso_fecha_corte1": "2025-02-01T00:00:00Z",
  "permiso_fecha_corte2": null,
  "permiso_fecha_corte3": null,
  "permiso_fecha_corte4": null
}
```
- Reglas de negocio:
  - `user_id` debe ser un usuario con `tipo_usuario = 'docente'`
  - El `grado_id` de `not_asignatura_grado` debe coincidir con el `grado_id` del `grupo`
  - No se permiten duplicados por `(user_id, asignatura_grado_id, grupo_id)`
- Respuesta exitosa (201):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 12,
    "asignatura_grado_id": 123,
    "grupo_id": 77,
    "permiso_fecha_corte1": "2025-02-01T00:00:00Z",
    "permiso_fecha_corte2": null,
    "permiso_fecha_corte3": null,
    "permiso_fecha_corte4": null,
    "created_at": "2025-12-12T12:00:00Z",
    "updated_at": "2025-12-12T12:00:00Z"
  },
  "message": "Asignación creada"
}
```
- Errores
  - 422: validación (ids inexistentes, formato de fechas)
  - 500: reglas de negocio ("Usuario no es docente", "Grado del grupo no coincide", "Asignación duplicada")

### 4. Crear asignaciones en bloque
- Método: `POST`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/bulk`
- Body (JSON):
```json
{
  "user_id": 12,
  "items": [
    { "asignatura_grado_id": 123, "grupo_id": 77 },
    { "asignatura_grado_id": 124, "grupo_id": 78 }
  ]
}
```
- Nota: `docenteId` en la ruta debe coincidir con `user_id` en el body.
- Respuesta exitosa (201):
```json
{
  "success": true,
  "data": [ { "id": 1 }, { "id": 2 } ],
  "message": "Asignaciones creadas"
}
```
- Errores: iguales a creación individual (422, 500)

### 5. Obtener detalle de asignación
- Método: `GET`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/{id}`
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 12,
    "asignatura_grado_id": 123,
    "grupo_id": 77,
    "permiso_fecha_corte1": null,
    "permiso_fecha_corte2": null,
    "permiso_fecha_corte3": null,
    "permiso_fecha_corte4": null,
    "asignatura_grado": { "id": 123, "grado_id": 3, "materia_id": 55 },
    "grupo": { "id": 77, "grado_id": 3 }
  },
  "message": "Asignación"
}
```
- 404: recurso no encontrado

### 6. Actualizar permisos de cortes
- Método: `PUT`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/{id}`
- Body (JSON):
```json
{
  "permiso_fecha_corte1": "2025-02-10T00:00:00Z",
  "permiso_fecha_corte2": null,
  "permiso_fecha_corte3": null,
  "permiso_fecha_corte4": null
}
```
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": { "id": 1, "permiso_fecha_corte1": "2025-02-10T00:00:00Z" },
  "message": "Asignación actualizada"
}
```
- 422: errores de validación de fechas

### 7. Eliminar asignación
- Método: `DELETE`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/{id}`
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": null,
  "message": "Asignación eliminada"
}
```
- 404: recurso no encontrado

## Notas
- Campos de permiso de cortes (`permiso_fecha_corte1..4`) son ventanas en las que el docente puede editar calificaciones.
- Eliminación es lógica (soft delete) y se registra auditoría (`created_by`, `updated_by`, `deleted_by`).
- Índice único garantiza no duplicar asignaciones por docente/asignatura/grupo.

## Referencias de Código
- Controlador: `app/Http/Controllers/Api/V1/AsignacionDocenteController.php:1`
- Servicio: `app/Services/AsignaturaGradoDocenteService.php:1`
- Repositorio: `app/Repositories/AsignaturaGradoDocenteRepository.php:1`
- Modelo: `app/Models/NotAsignaturaGradoDocente.php:1`
- Rutas: `routes/api/v1/usuarios-docentes-asignaciones.php:1`, `routes/api.php:17`
- Auditoría: `app/Http/Controllers/Api/V1/AuditController.php:45`

### 8. Listar todas las asignaturas
- Método: `GET`
- URL: `/api/v1/usuarios/docentes/asignaturas`
- Parámetros:
  - `periodo_lectivo_id` (opcional): Filtra por período lectivo
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "periodo_lectivo_id": 10,
      "grado_id": 3,
      "periodo_lectivo": "2025",
      "grupo": { "id": 77, "nombre": "1-A (VESPERTINO)" },
      "asignatura": { "id": 55, "nombre": "FISICA" },
      "permiso_fecha_corte1": "2025-02-10T00:00:00Z",
      "permiso_fecha_corte2": null,
      "permiso_fecha_corte3": null,
      "permiso_fecha_corte4": null
    }
  ],
  "message": "Asignaturas"
}
```
- Ordenado por grupo y asignatura.

### 9. Actualizar permisos de cortes en bloque
- Método: `PUT`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/permisos/bulk`
- Body (JSON):
```json
{
  "items": [
    { "id": 1, "permiso_fecha_corte1": "2025-02-10T00:00:00Z" },
    { "id": 2, "permiso_fecha_corte2": null, "permiso_fecha_corte3": "2025-03-01T00:00:00Z" }
  ]
}
```
- Reglas:
  - Todas las `id` deben pertenecer al docente indicado en `docenteId`.
  - Fechas en formato válido ISO; valores `null` para limpiar permisos.
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "permiso_fecha_corte1": "2025-02-10T00:00:00Z",
      "permiso_fecha_corte2": null,
      "permiso_fecha_corte3": null,
      "permiso_fecha_corte4": null
    },
    {
      "id": 2,
      "permiso_fecha_corte1": null,
      "permiso_fecha_corte2": null,
      "permiso_fecha_corte3": "2025-03-01T00:00:00Z",
      "permiso_fecha_corte4": null
    }
  ],
  "message": "Permisos actualizados"
}
```

### 10. Trasladar asignaciones a otro docente (bulk)
- Método: `PUT`
- URL: `/api/v1/usuarios/docentes/{docenteId}/asignaciones/trasladar/bulk`
- Body (JSON):
```json
{
  "to_user_id": 99,
  "items": [
    { "id": 1 },
    { "id": 2 }
  ]
}
```
- Reglas:
  - `to_user_id` debe ser un usuario con `tipo_usuario = 'docente'`.
  - Todas las `id` deben pertenecer al docente origen (`docenteId`).
  - Si existe una asignación duplicada para el docente destino, se rechaza la operación.
- Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    { "id": 1, "user_id": 99, "asignatura_grado_id": 123, "grupo_id": 77 },
    { "id": 2, "user_id": 99, "asignatura_grado_id": 124, "grupo_id": 78 }
  ],
  "message": "Asignaciones trasladadas"
}
```
