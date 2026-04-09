# API — Listas por Grupo

## Prefijo
- `Base`: `/api/v1/listas-grupo`
- `Permiso requerido`: `ver_listas_grupo`

## 1) Catálogos (Períodos, Turnos, Grupos)
- `GET /api/v1/listas-grupo/catalogos`
- `Query params`
  - `periodo_lectivo_id` (opcional): filtra los grupos por período
  - `turno_id` (opcional): filtra los grupos por turno
- `Respuesta`
```json
{
  "success": true,
  "data": {
    "periodos_lectivos": [ { "id": 5, "nombre": "2025" } ],
    "turnos": [ { "id": 1, "nombre": "Mañana", "orden": 1 } ],
    "grupos": [
      { "id": 10, "nombre": "3ro+A" }
    ]
  },
  "message": "Catálogos obtenidos exitosamente"
}
```

## 2) Listar alumnos por grupo
- `GET /api/v1/listas-grupo/alumnos`
- `Query params`
  - `periodo_lectivo_id` (opcional)
  - `grupo_id` (opcional)
  - `turno_id` (opcional)
- `Orden`: `sexo DESC`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`
- `Respuesta`
```json
{
  "success": true,
  "data": [
    {
      "user_id": 123,
      "nombre_completo": "Juan Carlos Pérez García",
      "correo": "juan@example.com",
      "sexo": "M",
      "grupo_id": 10,
      "grupo_nombre": "3ro+A"
    }
  ],
  "message": "Alumnos obtenidos exitosamente"
}
```

## 3) Imprimir PDF de la lista
- `GET /api/v1/listas-grupo/alumnos/pdf`
- `Query params`
  - `periodo_lectivo_id` (recomendado)
  - `grupo_id` (recomendado)
- `Respuesta`: descarga `application/pdf`
- `Vista`: muestra columnas `#`, `Nombre Completo`, `Correo`, `Sexo` y encabezado con `Docente Guía`. Al final se incluye “Resumen por sexo” (Femenino, Masculino y Total).

## 4) Exportar Excel de la lista
- `GET /api/v1/listas-grupo/alumnos/excel`
- `Query params`
  - `periodo_lectivo_id` (recomendado)
  - `grupo_id` (recomendado)
- `Respuesta`: descarga `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` con filename `lista_alumnos_grupo.xlsx`
- `Columnas`: `#`, `nombre_completo`, `correo`, `sexo` (valores: `Masculino`, `Femenino`). En el encabezado del archivo aparecen filas informativas con `Período` y `Grupo`. Al final se incluye “Resumen por sexo” con filas: Femenino, Masculino y Total.

## Notas
- Las respuestas siguen el estándar `{ success, data, message }`.
- Se requiere el permiso `ver_listas_grupo` para acceder a todos los endpoints.
- La consulta de alumnos excluye registros `deleted_at` y sólo incluye `users_grupos.estado = 'activo'`.

## Referencias Técnicas
- Controlador: `app/Http/Controllers/Api/V1/ListasGrupoController.php`
  - `catalogos`
  - `alumnos`
  - `alumnosPdf`
  - `alumnosExcel`
- Rutas: `routes/api/v1/listas_grupo.php`
- Vista PDF: `resources/views/reportes/listas_grupo/lista-alumnos.blade.php`