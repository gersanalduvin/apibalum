# Reporte: Cuentas x Cobrar

- Controlador: `App\\Http\\Controllers\\Api\\V1\\ReporteCuentaXCobrarController`
- Servicio: `App\\Services\\ReporteCuentaXCobrarService`
- Rutas base: `GET /api/v1/reportes/cuenta-x-cobrar/...`
- Permiso: `reporte_cuenta_x_cobrar.ver`

## Endpoints

1. Periodos lectivos y turnos
- Método: `GET /api/v1/reportes/cuenta-x-cobrar/periodos-turnos`
- Descripción: Devuelve el listado de periodos lectivos y turnos disponibles.
- Respuesta:
```json
{
  "success": true,
  "data": {
    "periodos_lectivos": [
      {"id": 1, "uuid": "...", "nombre": "2025", "periodo_matricula": "2024-12", "periodo_nota": "2025"}
    ],
    "turnos": [
      {"id": 1, "uuid": "...", "nombre": "Mañana", "orden": 1}
    ]
  },
  "message": "Listado de periodos lectivos y turnos"
}
```

2. Grupos por período lectivo y turno
- Método: `GET /api/v1/reportes/cuenta-x-cobrar/grupos`
- Query params: `periodo_lectivo_id` (requerido), `turno_id` (requerido)
- Descripción: Devuelve los grupos filtrados por período lectivo y turno.
- Respuesta:
```json
{
  "success": true,
  "data": [
    {"id": 10, "uuid": "...", "grado_id": 3, "seccion_id": 2, "turno_id": 1, "periodo_lectivo_id": 1}
  ],
  "message": "Grupos filtrados por período lectivo y turno"
}
```

3. Resumen de users_aranceles por alumno con totales
- Método: `GET /api/v1/reportes/cuenta-x-cobrar/usuarios-aranceles`
- Query params:
  - `periodo_lectivo_id` (requerido)
  - `turno_id` (opcional)
  - `grupo_id` (opcional; aceptar `Todos` para incluir todos los grupos)
  - `meses[]` (opcional; lista de meses a visualizar: `enero`…`diciembre`)
- Descripción:
  - Devuelve filas por alumno con columnas de meses seleccionados y `mat` (matrícula pendiente si existe), y una columna `total` al final que suma los meses seleccionados pendientes.
  - Incluye `totales_por_mes` (suma de todos los alumnos por cada mes seleccionado) y `total_general` (suma total de todos los meses seleccionados).
- Respuesta:
```json
{
  "success": true,
  "data": {
    "rows": [
      {
        "alumno": "Juan Pérez",
        "mat": "500.00",
        "ene": "",
        "feb": "600.00",
        "mar": "",
        "abr": "",
        "may": "",
        "jun": "",
        "jul": "",
        "ago": "",
        "sep": "",
        "oct": "",
        "nov": "",
        "dic": "",
        "total": "600.00"
      }
    ],
    "totales_por_mes": {
      "ene": "",
      "feb": "1200.00",
      "mar": ""
    },
    "total_general": "1200.00"
  },
  "message": "Resumen de cuentas por cobrar por alumno"
}
```

4. Exportar PDF del reporte
- Método: `POST /api/v1/reportes/cuenta-x-cobrar/export/pdf`
- Body: igual que el endpoint de resumen (`periodo_lectivo_id`, `turno_id`, `grupo_id`=`id|Todos`, `meses[]`)
- Descripción:
  - Genera un PDF con la grilla del reporte. Si `grupo_id=Todos`, cada grupo se renderiza en una página nueva. Se incluye una página final con el “Resumen General” (totales por mes y total general).
- Permiso: `reporte_cuenta_x_cobrar.exportar_pdf`
 - Encabezado: Se incluye encabezado institucional con título "Cuentas x Cobrar" y subtítulos dinámicos de Período y Turno.

## Notas
- La columna `mat` se determina detectando aranceles pendientes con rubros sin mes asociado y nombre que contenga "matric".
- Los montos mostrados corresponden a `saldo_actual` de cada arancel pendiente.
- La columna `total` suma únicamente los meses seleccionados en `meses[]`.
- Si `grupo_id=Todos` se incluyen alumnos de todos los grupos del período (filtrando por `turno_id` si se pasa).
- Todos los endpoints requieren el permiso `reporte_cuenta_x_cobrar.ver`.
