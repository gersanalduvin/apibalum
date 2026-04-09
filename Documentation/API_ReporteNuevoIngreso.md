# API Reporte: Lista nuevo ingreso

## Permiso requerido
- `repote.nuevoingreso`

## Endpoints
- `GET /api/v1/reportes/nuevo-ingreso/periodos-lectivos`
  - Descripción: Devuelve la lista de períodos lectivos disponibles.
  - Respuesta 200:
    {
      "success": true,
      "data": [
        { "id": 1, "nombre": "2025" },
        { "id": 2, "nombre": "2026" }
      ],
      "message": "Períodos lectivos obtenidos exitosamente"
    }

- `GET /api/v1/reportes/nuevo-ingreso/export?periodo_lectivo_id={id}`
  - Descripción: Exporta en PDF (legal, horizontal) los alumnos con `users_grupos.tipo_ingreso = nuevo_ingreso` y `deleted_at = null`.
  - Parámetros:
    - `periodo_lectivo_id` (int, requerido)
  - Respuesta:
    - Archivo PDF con las columnas:
      - `codigo_unico`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `fecha_nacimiento (dd/mm/YYYY)`, `sexo`, `lugar_nacimiento`,
        `nombre_madre`, `cedula_madre`, `telefono_tigo_madre`, `telefono_claro_madre`, `direccion_madre`,
        `nombre_padre`, `cedula_padre`, `telefono_tigo_padre`, `direccion_padre`,
        `fecha_matricula (dd/mm/YYYY)`, `grado`, `modalidad`, `turno`.
    - Content-Type: `application/pdf`
  - Configuración visual:
    - Orientación: horizontal, tamaño legal, márgenes 0.5"
    - Fuente: Arial 10pt, encabezados en todas las páginas, numeración de páginas en pie de página

## Filtros aplicados
- `users_grupos.periodo_lectivo_id = :id`
- `users_grupos.tipo_ingreso = 'nuevo_ingreso'`
- `users_grupos.deleted_at IS NULL`
- `users.deleted_at IS NULL`
- `users_grupos.estado = 'activo'`

## Notas
- Fechas `fecha_nacimiento` y `fecha_matricula` se formatean como `dd/mm/YYYY`.
- Los valores de `grado`, `modalidad` y `turno` provienen de `config_grado`, `config_modalidad` y `config_turnos` respectivamente.