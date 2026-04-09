# Configuración - Asignaturas por Grado

- Módulo: Configuración Académica
- Permisos requeridos: `not_asignatura_grado.index`, `not_asignatura_grado.create`, `not_asignatura_grado.delete`
- Auditoría: Habilitada automáticamente (trait Auditable). Modelos registrados en `AuditController`.

## Endpoints

### Periodos lectivos y grados
- `GET /api/v1/not-asignatura-grado/periodos-y-grados`
- Permiso: `not_asignatura_grado.index`
- Devuelve arreglo con `periodos`, `grados`, `escalas` y `materias`.

### Alternativas y parciales
- `GET /api/v1/not-asignatura-grado/alternativas`
- Permiso: `not_asignatura_grado.index`
- Parámetros:
  - `periodo_lectivo_id` (requerido)
  - `grado_id` (requerido)
  - `asignatura_grado_id` (opcional, excluye ese id de los resultados)
- Devuelve arreglo con:
  - `asignaturas`: `not_asignatura_grado` filtradas por `periodo_lectivo_id` y `grado_id`, excluyendo `asignatura_grado_id` si se envía
  - `parciales`: `config_not_semestre_parciales` filtrados por `periodo_lectivo_id`

### Listar asignaturas por grado (paginado)
- `GET /api/v1/not-asignatura-grado`
- Permiso: `not_asignatura_grado.index`
- Parámetros:
  - `per_page` (opcional)
  - `periodo_lectivo_id` (opcional)
  - `grado_id` (opcional)
  - `materia` (opcional, búsqueda por nombre/abreviatura)

### Listar asignaturas por grado (todos)
- `GET /api/v1/not-asignatura-grado/getall`
- Permiso: `not_asignatura_grado.index`

### Obtener detalle de asignatura por grado
- `GET /api/v1/not-asignatura-grado/{id}`
- Permiso: `not_asignatura_grado.show`

### Actualizar asignatura por grado y relaciones
- `PUT /api/v1/not-asignatura-grado/{id}`
- Permiso: `not_asignatura_grado.update`
- Body: igual al de creación, enviando todas las relaciones a actualizar

### Crear/Actualizar asignatura por grado y relaciones
- `POST /api/v1/not-asignatura-grado`
- Permiso: `not_asignatura_grado.create`
- Body (JSON):
```
{
  "id": 1, // opcional para actualizar
  "periodo_lectivo_id": 10,
  "grado_id": 3,
  "materia_id": 55,
  "escala_id": 2,
  "nota_aprobar": 60,
  "nota_maxima": 100,
  "incluir_en_promedio": true,
  "incluir_en_reporte_mined": false,
  "tipo_evaluacion": "sumativa",
  "es_para_educacion_iniciativa": false,
  "cortes": [
    {
      "id": 5, // opcional
      "corte_id": 12,
      "evidencias": [
        { "id": 1, "evidencia": "Examen parcial", "indicador": {"criterio": "Comprensión"} },
        { "evidencia": "Proyecto", "indicador": null }
      ]
    }
  ],
  "parametros": [
    { "id": 1, "parametro": "ponderacion_parcial", "valor": "30" },
    { "parametro": "min_evidencias", "valor": "2" }
  ],
  "hijas": [
    { "asignatura_hija_id": 99 }
  ]
}
```

### Eliminar asignatura por grado (y sus relaciones)
- `DELETE /api/v1/not-asignatura-grado/{id}`
- Permiso: `not_asignatura_grado.delete`

### Exportar a PDF
- `GET /api/v1/not-asignatura-grado/export/pdf`
- Permiso: `not_asignatura_grado.index`
- Filtros opcionales: `periodo_lectivo_id`, `grado_id`, `materia`

### Exportar a Excel
- `GET /api/v1/not-asignatura-grado/export/excel`
- Permiso: `not_asignatura_grado.index`
- Filtros opcionales: `periodo_lectivo_id`, `grado_id`, `materia`

## Modelos y Auditoría

### Modelos
- `App\Models\NotAsignaturaGrado` (`not_asignatura_grado`)
- `App\Models\NotAsignaturaGradoCorte` (`not_asignatura_grado_cortes`)
- `App\Models\NotAsignaturaGradoCorteEvidencia` (`not_asignatura_grado_cortes_evidencias`)
- `App\Models\NotAsignaturaParametro` (`not_asignatura_parametros`)
- `App\Models\NotAsignaturaGradoHija` (`not_asignatura_grado_hijas`)

Todos incluyen:
- Campos auditoría: `created_by`, `updated_by`, `deleted_by`, `deleted_at`
- Campos de sincronización: `uuid`, `is_synced`, `synced_at`, `updated_locally_at`, `version`

### AuditController
- Modelos registrados:
  - `not_asignatura_grado`
  - `not_asignatura_grado_cortes`
  - `not_asignatura_grado_cortes_evidencias`
  - `not_asignatura_parametros`
  - `not_asignatura_grado_hijas`

## Guía Frontend
- Cargar selects con `GET /api/v1/not-asignatura-grado/periodos-y-grados`.
- Para cortes, usar catálogos desde `config-not-semestre` si se necesitan nombres/fechas.
- En el formulario, permitir agregar/remover:
  - Cortes (y evidencias por corte)
  - Parámetros (clave-valor)
  - Asignaturas hijas
- En edición, enviar `id` en elementos existentes para upsert.
