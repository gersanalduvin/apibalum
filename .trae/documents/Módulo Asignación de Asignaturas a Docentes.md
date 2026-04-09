## Objetivo
- Gestionar asignaciones de asignaturas (por grado) a docentes por grupo, con ventanas de permiso por corte.
- Incluir un endpoint para listar TODAS las asignaturas NO asignadas globalmente (sin parámetros, sin paginación).

## Entidades y Relaciones
- Tabla: `not_asignatura_grado_docente`
  - `id`, `user_id` (docente), `asignatura_grado_id`, `grupo_id`
  - `permiso_fecha_corte1..4` (`datetime`, nullable)
  - Auditoría y soft delete: `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `timestamps`
  - Índices: `user_id`, `asignatura_grado_id`, `grupo_id`, `unique(user_id, asignatura_grado_id, grupo_id)`

## Migración
- Crear claves foráneas y el índice único.
- Agregar campos de auditoría + soft deletes.

## Modelo
- `App\Models\NotAsignaturaGradoDocente`
  - `use SoftDeletes, Auditable`
  - `fillable` y `casts` (permiso fechas → `datetime`)
  - Relaciones: `user()`, `asignaturaGrado()`, `grupo()`

## Repository
- `AsignaturaGradoDocenteRepository` (CRUD + consultas)
- NUEVO: `getGloballyUnassignedAsignaturas(): Collection`
  - Retorna todos los registros de `not_asignatura_grado` que NO tienen ninguna entrada en `not_asignatura_grado_docente` (consulta con `whereNotExists`/`leftJoin ... where null`).

## Service
- `AsignaturaGradoDocenteService`
  - `assign(...)`, `assignBulk(...)`, `updatePermisos(...)`, `unassign(...)`, `getByDocente(...)`
  - NUEVO: `getGloballyUnassignedAsignaturas(): Collection`

## Form Requests
- `AsignacionStoreRequest`, `AsignacionUpdateRequest`, `AsignacionBulkRequest`
- No se requiere request para el endpoint global de no asignadas.

## Controller (API)
- `AsignacionDocenteController`
  - CRUD: `indexByDocente`, `store`, `storeBulk`, `show`, `update`, `destroy`
  - NUEVO: `unassignedGlobal()`
    - `GET /usuarios/docentes/asignaciones/no-asignadas`
    - Sin parámetros, sin paginación, devuelve `not_asignatura_grado` no asignadas a ningún docente en ningún grupo.

## Rutas
- Archivo: `routes/api/v1/usuarios-docentes-asignaciones.php`
  - CRUD de asignaciones por docente y grupo
  - NUEVO: `GET /usuarios/docentes/asignaciones/no-asignadas` → `unassignedGlobal`
- Middleware: `auth:sanctum` + `check.permissions:usuarios.docentes.asignar_materias`

## Auditoría
- Trait `Auditable` y registro en `AuditController::$models`.

## Respuestas JSON
- Estándar del proyecto: `success`, `data`, `message`.

## Pruebas
- Verificar que el endpoint global retorna vacío cuando todas están asignadas y retorna todas cuando no hay asignaciones.
- Pruebas de duplicado (rechazo), creación, actualización de permisos y eliminación.

## Performance
- Asegurar índices en `not_asignatura_grado_docente.asignatura_grado_id` para el `whereNotExists`.

## Seguridad
- Permisos en el endpoint global: `usuarios.docentes.asignar_materias`.

## Siguientes Pasos
1. Crear migración y modelo.
2. Implementar Repository/Service incluyendo `getGloballyUnassignedAsignaturas`.
3. Crear Controller y rutas.
4. Registrar en `AuditController` y documentar.
5. Añadir pruebas.

¿Confirmas este plan para comenzar la implementación?