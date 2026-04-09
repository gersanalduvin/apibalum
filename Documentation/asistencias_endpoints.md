# Módulo Asistencias

## Permisos
- `asistencias.ver`: requerido para endpoints GET
- `asistencias.registrar`: requerido para POST/PUT/DELETE y exportaciones

## Endpoints

### GET `/api/v1/grupos/{grupo_id}/usuarios`
- Retorna usuarios de un grupo con relación a `users`
- Parámetros: `grupo_id`
- Respuesta:
  - `data[]`: `{ id, nombre, email }`

### GET `/api/v1/asistencias/grupo/{grupo_id}/fecha/{fecha}/corte/{corte}`
- Obtiene excepciones (ausencias/tardes) registradas para fecha y corte
- Parámetros: `grupo_id`, `fecha` (`YYYY-MM-DD`), `corte` (`corte_1|corte_2|corte_3|corte_4`)
- Respuesta:
  - `data[]`: `{ id, user_id, estado, justificacion, hora_registro }`

### POST `/api/v1/asistencias/registrar-grupo`
- Registra excepciones de asistencia para un grupo en bloque
- Body:
```
{
  "grupo_id": 123,
  "fecha": "YYYY-MM-DD",
  "corte": "corte_1",
  "excepciones": [
    { "user_id": 1, "estado": "ausencia_injustificada" },
    { "user_id": 2, "estado": "tarde_justificada", "justificacion": "Tráfico", "hora_registro": "08:15" }
  ]
}
```
- Validaciones:
  - `fecha` no futura
  - `corte` válido
  - `user_id` debe existir en `users_grupo` para el `grupo_id`
  - `justificacion` obligatoria para estados justificados
  - `hora_registro` obligatoria para llegadas tarde
  - Transacciones y unicidad (`user_id + fecha + corte`)
- Respuesta `201` con registros creados

### PUT `/api/v1/asistencias/{id}`
- Actualiza `estado`, `justificacion` o `hora_registro`
- No permite cambiar `corte`
- Métodos aceptados: `PUT` y `PATCH`
- Estados permitidos: `ausencia_justificada`, `ausencia_injustificada`, `tarde_justificada`, `tarde_injustificada`, `presente`
- Reglas y valores por defecto:
  - Si se envía `estado` de tipo justificado y no se incluye `justificacion`, se asigna "Justificado" automáticamente
  - Si se envía `estado` de tipo tardanza y no se incluye `hora_registro`, se asigna la hora actual automáticamente (formato `HH:MM`)
  - Si se envía `estado = "presente"`, el sistema elimina el registro de excepción (equivalente a `DELETE`)
- Body (ejemplos):
```
{ "estado": "tarde_injustificada", "hora_registro": "08:10" }
{ "estado": "ausencia_justificada", "justificacion": "Cita médica" }
{ "estado": "presente" }
```

### DELETE `/api/v1/asistencias/{id}`
- Elimina la excepción registrada (forma recomendada para marcar "presente")

### GET `/api/v1/asistencias/reporte/{grupo_id}/corte/{corte}`
- Filtra únicamente por grupo y corte
#### Respuesta:
```
{
  "usuarios": [
    {
      "user_id": 1,
      "nombre": "Nombre Apellido",
      "ausencias_justificadas": 1,
      "ausencias_injustificadas": 1,
      "tardes_justificadas": 0,
      "tardes_injustificadas": 2,
      "porcentaje_asistencia": 90.0,
      "porcentaje_llegada_tarde": 40.0
    }
  ],
  "totales": {
    "ausencias_justificadas": 10,
    "ausencias_injustificadas": 8,
    "tardes_justificadas": 5,
    "tardes_injustificadas": 12,
    "promedio_asistencia": 92.5,
    "promedio_llegada_tarde": 23.0
  }
}
```

- ### GET `/api/v1/asistencias/reporte-general/{grupo_id}`
- Filtra únicamente por grupo
- Respuesta:
```
{
  "alumnos": [
    {
      "user_id": 1,
      "nombre": "Nombre Apellido",
      "cortes": {
        "corte_1": {
          "ausencias_justificadas": 1,
          "ausencias_injustificadas": 0,
          "tardes_justificadas": 1,
          "tardes_injustificadas": 1,
          "porcentaje_asistencia": 95.0,
          "porcentaje_llegada_tarde": 10.0
        },
        "corte_2": { "ausencias_justificadas": 0, "ausencias_injustificadas": 1, "tardes_justificadas": 0, "tardes_injustificadas": 2, "porcentaje_asistencia": 92.0, "porcentaje_llegada_tarde": 8.0 },
        "corte_3": { "ausencias_justificadas": 0, "ausencias_injustificadas": 0, "tardes_justificadas": 0, "tardes_injustificadas": 0, "porcentaje_asistencia": 100.0, "porcentaje_llegada_tarde": 0.0 },
        "corte_4": { "ausencias_justificadas": 0, "ausencias_injustificadas": 0, "tardes_justificadas": 0, "tardes_injustificadas": 0, "porcentaje_asistencia": 98.0, "porcentaje_llegada_tarde": 5.0 }
      },
      "promedio_asistencia": 96.25,
      "promedio_llegada_tarde": 5.75
    }
  ],
  "por_corte": {
    "corte_1": {
      "usuarios": [ { "user_id": 1, "nombre": "Nombre Apellido", "ausencias_justificadas": 1, "ausencias_injustificadas": 0, "tardes_justificadas": 1, "tardes_injustificadas": 1, "porcentaje_asistencia": 95.0, "porcentaje_llegada_tarde": 10.0 } ],
      "totales": { "ausencias_justificadas": 5, "ausencias_injustificadas": 3, "tardes_justificadas": 4, "tardes_injustificadas": 6, "promedio_asistencia": 93.5, "promedio_llegada_tarde": 12.0 }
    },
    "corte_2": { "usuarios": [], "totales": { "ausencias_justificadas": 0, "ausencias_injustificadas": 0, "tardes_justificadas": 0, "tardes_injustificadas": 0, "promedio_asistencia": 0.0, "promedio_llegada_tarde": 0.0 } },
    "corte_3": { "usuarios": [], "totales": { "ausencias_justificadas": 0, "ausencias_injustificadas": 0, "tardes_justificadas": 0, "tardes_injustificadas": 0, "promedio_asistencia": 0.0, "promedio_llegada_tarde": 0.0 } },
    "corte_4": { "usuarios": [], "totales": { "ausencias_justificadas": 0, "ausencias_injustificadas": 0, "tardes_justificadas": 0, "tardes_injustificadas": 0, "promedio_asistencia": 0.0, "promedio_llegada_tarde": 0.0 } }
  },
  "promedio_general_asistencia": 92.75,
  "promedio_general_llegada_tarde": 18.5
}
```

- Reglas de cálculo:
  - `TotalDias`: se extrae de `config_not_semestre_parciales` usando `fecha_inicio_corte` y `fecha_fin_corte`, filtrando por `periodo_lectivo_id` (relación `config_not_semestre`) y `orden` (1=corte_1, 2=corte_2, 3=corte_3, 4=corte_4).
  - `Asistencias = TotalDias – Ausencias – penalización_por_tardanzas` (actualmente sin penalización: 0).
  - `% Asistencia = (Asistencias / TotalDias) × 100`.
  - `% Llegadas Tarde = (tardes_justificadas + tardes_injustificadas) / TotalDias × 100`.

- ### GET `/api/v1/asistencias/reporte/{grupo_id}/corte/{corte}/export?format=pdf|xlsx`
- Exporta reporte por corte a PDF o Excel (sin parámetros de fecha)
- Respuesta: archivo en `data.content` (Base64) y `data.filename`

- ### GET `/api/v1/asistencias/reporte-general/{grupo_id}/export?format=pdf|xlsx`
- Exporta reporte general a PDF o Excel (sin parámetros de fecha). Incluye columnas: `Alumno`, por corte (`AJ`, `AI`, `LLT`, `LLTI`, `%A`, `%LLT`) y `PROM %A`, `PROM %LLT`.

### GET `/api/v1/asistencias/reporte-general-por-grupo?periodo_lectivo_id={id}`
- Estructura por grupo con métricas por corte (filtra solo por período lectivo)
- Respuesta:
```
{
  "rows": [
    {
      "grupo": "1 GRADO - A",
      "turno": "Matutino",
      "cortes": {
        "corte_1": { "AJ": 1, "AI": 1, "LLT": 0, "LLTI": 0, "%A": 99.0, "%LLT": 20.0 },
        "corte_2": { "AJ": 0, "AI": 0, "LLT": 0, "LLTI": 0, "%A": 98.5, "%LLT": 12.0 },
        "corte_3": { "AJ": 0, "AI": 0, "LLT": 0, "LLTI": 0, "%A": 97.0, "%LLT": 8.0 },
        "corte_4": { "AJ": 0, "AI": 0, "LLT": 0, "LLTI": 0, "%A": 96.0, "%LLT": 5.0 }
      },
      "promedio_asistencia": 97.88,
      "promedio_llegada_tarde": 11.25
    }
  ],
  "promedio_total_por_corte": {
    "corte_1": { "%A": 95.2, "%LLT": 10.0 },
    "corte_2": { "%A": 94.8, "%LLT": 9.5 },
    "corte_3": { "%A": 93.7, "%LLT": 8.2 },
    "corte_4": { "%A": 92.9, "%LLT": 7.8 }
  },
  "promedio_general_asistencia": 94.65,
  "promedio_general_llegada_tarde": 8.88
}
```

- ### GET `/api/v1/asistencias/reporte-general-por-grupo/export?format=pdf|xlsx&periodo_lectivo_id={id}`
- Exporta a PDF o Excel con columnas: `Grupo`, `Turno`, por corte (`AJ`, `AI`, `LLT`, `LLTI`, `%A`, `%LLT`) y `PROM %A`, `PROM %LLT`. Incluye fila final "PROMEDIO TOTAL" con promedios por corte y globales.

### GET `/api/v1/asistencias/reporte-general-por-grado?periodo_lectivo_id={id}`
- Estructura por grado y turno con métricas por corte (filtra solo por período lectivo)
- Respuesta:
```
{
  "rows": [
    {
      "grado": "1 GRADO",
      "turno": "Matutino",
      "cortes": {
        "corte_1": { "AJ": 2, "AI": 1, "LLT": 1, "LLTI": 0, "%A": 98.5, "%LLT": 12.0 },
        "corte_2": { "AJ": 1, "AI": 0, "LLT": 0, "LLTI": 1, "%A": 97.2, "%LLT": 8.5 },
        "corte_3": { "AJ": 0, "AI": 1, "LLT": 0, "LLTI": 0, "%A": 96.0, "%LLT": 5.0 },
        "corte_4": { "AJ": 0, "AI": 0, "LLT": 0, "LLTI": 0, "%A": 99.0, "%LLT": 3.0 }
      },
      "promedio_asistencia": 97.68,
      "promedio_llegada_tarde": 7.13
    }
  ],
  "promedio_total_por_corte": {
    "corte_1": { "%A": 95.2, "%LLT": 10.0 },
    "corte_2": { "%A": 94.8, "%LLT": 9.5 },
    "corte_3": { "%A": 93.7, "%LLT": 8.2 },
    "corte_4": { "%A": 92.9, "%LLT": 7.8 }
  },
  "promedio_general_asistencia": 94.65,
  "promedio_general_llegada_tarde": 8.88
}
```

- ### GET `/api/v1/asistencias/reporte-general-por-grado/export?format=pdf|xlsx&periodo_lectivo_id={id}`
- Exporta a PDF o Excel con columnas: `Grado`, `Turno`, por corte (`AJ`, `AI`, `LLT`, `LLTI`, `%A`, `%LLT`) y `PROM %A`, `PROM %LLT`. Incluye fila final "PROMEDIO TOTAL" con promedios por corte y globales.

### GET `/api/v1/asistencias/periodos-lectivos`
- Lista periodos lectivos disponibles

### GET `/api/v1/asistencias/grupos-por-turno?periodo_id={id}`
- Retorna grupos del período agrupados por turno
- Respuesta:
```
{
  "Matutino": [ { "id": 1, "nombre": "1° A" } ],
  "Vespertino": [ { "id": 2, "nombre": "1° B" } ]
}
```

## Auditoría
- Modelo auditado: `asistencias` registrado en `AuditController::$models`
- Campos auditables por defecto a través del trait `Auditable`
 
## Notas para frontend
- Para marcar "presente": preferir `DELETE /api/v1/asistencias/{id}`; alternativamente `PUT/PATCH` con `{"estado":"presente"}` (elimina el registro)
- Al setear tardanzas, enviar `hora_registro` si está disponible para mayor precisión; si se omite, el sistema registra la hora actual
- Al setear justificados, enviar `justificacion` si está disponible; si se omite, se completará como "Justificado"
