# Configuración - Cortes (Semestres y Parciales)

- Módulo: Configuración Académica
- Permisos requeridos: `config_not_semestre.index`, `config_not_semestre.create`, `config_not_semestre.delete`
- Auditoría: Habilitada automáticamente (trait Auditable). Consultable vía `AuditController`.

## Endpoints

### Periodos lectivos
- `GET /api/v1/config-not-semestre/periodos-lectivos`
- Permiso: `config_not_semestre.index`
- Descripción: devuelve todos los periodos lectivos disponibles (equivale a `ConfPeriodoLectivoController@getall`).

### Listar semestres (paginado)
- `GET /api/v1/config-not-semestre`
- Permiso: `config_not_semestre.index`
- Parámetros:
  - `per_page` (opcional)
  - `semestre` (opcional, búsqueda por nombre/abreviatura y parciales)
  - `periodo_lectivo_id` (opcional)

### Listar semestres (todos)
- `GET /api/v1/config-not-semestre/getall`
- Permiso: `config_not_semestre.index`

### Crear/Actualizar semestre y parciales
- `POST /api/v1/config-not-semestre`
- Permiso: `config_not_semestre.create`
- Body (JSON):
```
{
  "id": 1, // opcional para actualizar
  "nombre": "Semestre I",
  "abreviatura": "S1",
  "orden": 1,
  "periodo_lectivo_id": 10,
  "parciales": [
    {
      "id": 5, // opcional para actualizar
      "nombre": "Parcial 1",
      "abreviatura": "P1",
      "fecha_inicio_corte": "2025-01-10",
      "fecha_fin_corte": "2025-02-10",
      "fecha_inicio_publicacion_notas": "2025-02-11",
      "fecha_fin_publicacion_notas": "2025-02-20",
      "orden": 1
    }
  ]
}
```

### Eliminar semestre
- `DELETE /api/v1/config-not-semestre/{id}`
- Permiso: `config_not_semestre.delete`

### Eliminar parcial de semestre
- `DELETE /api/v1/config-not-semestre/parcial/{id}`
- Permiso: `config_not_semestre.delete`

### Exportar a PDF
- `GET /api/v1/config-not-semestre/export/pdf`
- Permiso: `config_not_semestre.index`
- Filtros opcionales: `semestre`, `periodo_lectivo_id`

### Exportar a Excel
- `GET /api/v1/config-not-semestre/export/excel`
- Permiso: `config_not_semestre.index`
- Filtros opcionales: `semestre`, `periodo_lectivo_id`

## Modelos y Auditoría

### Modelos
- `App\Models\ConfigNotSemestre` (`config_not_semestre`)
- `App\Models\ConfigNotSemestreParcial` (`config_not_semestre_parciales`)

Todos incluyen:
- Campos auditoría: `created_by`, `updated_by`, `deleted_by`, `deleted_at`
- Campos de sincronización: `uuid`, `is_synced`, `synced_at`, `updated_locally_at`, `version`

### AuditController
- Modelos registrados:
  - `config_not_semestre`
  - `config_not_semestre_parciales`
- Consulta de historial:
  - `GET /api/v1/audits/summary/{model}/{id}`
  - Ej.: `/api/v1/audits/summary/config_not_semestre/1`
