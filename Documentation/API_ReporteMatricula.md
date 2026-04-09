# API de Reportes de Matrícula

## Información General

- **URL Base**: `/api/v1/reporte-matricula`
- **Controlador**: `App\Http\Controllers\Api\V1\ReporteMatriculaController`
- **Servicio**: `App\Services\ReporteMatriculaService`
- **Middleware**: `check.permissions:reportes.estadistica_matricula`
- **Permiso Requerido**: `reportes.estadistica_matricula`

---

## Endpoints Disponibles

### 1. Obtener Estadísticas Completas

**Ruta**: `GET /api/v1/reporte-matricula/estadisticas`

**Descripción**: Obtiene todas las estadísticas de matrícula consolidadas para un período lectivo específico, con filtros opcionales por rango de fechas de matrícula y modalidad.

**Parámetros**:
- `periodo_lectivo_id` (integer, required): ID del período lectivo
- `fecha_inicio` (date, optional): Fecha de inicio del rango de matrícula (YYYY-MM-DD)
- `fecha_fin` (date, optional): Fecha de fin del rango de matrícula (YYYY-MM-DD)
- `modalidad_id` (integer|string, optional): ID de modalidad. Si se envía `Todos`, no se aplica filtro de modalidad.

**Ejemplo de Petición**:
```http
GET /api/v1/reporte-matricula/estadisticas?periodo_lectivo_id=1&fecha_inicio=2024-01-01&fecha_fin=2024-03-31&modalidad_id=Todos
```

**Ejemplo de Respuesta**:
```json
{
    "success": true,
    "data": {
        "periodo_lectivo": {
            "id": 1,
            "nombre": "2024-2025",
            "fecha_inicio": "2024-01-15",
            "fecha_fin": "2024-12-15"
        },
        "fecha_generacion": "2024-01-20 10:30:00",
        "estadisticas": {
            "por_grupo_turno": [
                {
                    "grupo": "1A",
                    "turno": "Mañana",
                    "total_matriculados": 25,
                    "masculino": 12,
                    "femenino": 13
                }
            ],
            "por_grado_turno": [
                {
                    "grado": "1",
                    "turno": "Mañana",
                    "total_matriculados": 50,
                    "masculino": 25,
                    "femenino": 25
                }
            ],
            "por_dia": [
                {
                    "fecha": "2024-01-15",
                    "total_matriculados": 15,
                    "masculino": 8,
                    "femenino": 7
                }
            ],
            "por_usuario": [
                {
                    "usuario_id": 1,
                    "usuario_nombre": "Juan Pérez",
                    "total_matriculados": 10,
                    "masculino": 5,
                    "femenino": 5
                }
            ]
        }
    },
    "message": "Estadísticas obtenidas correctamente"
}
```

---

### 2. Generar PDF de Estadísticas

**Ruta**: `POST /api/v1/reporte-matricula/pdf/estadisticas`

**Descripción**: Genera un archivo PDF con todas las estadísticas de matrícula consolidadas, aplicando los mismos filtros opcionales de fechas y modalidad.

**Parámetros**:
- `periodo_lectivo_id` (integer, required): ID del período lectivo
- `fecha_inicio` (date, optional): Fecha de inicio del rango de matrícula (YYYY-MM-DD)
- `fecha_fin` (date, optional): Fecha de fin del rango de matrícula (YYYY-MM-DD)
- `modalidad_id` (integer|string, optional): ID de modalidad. Si se envía `Todos`, no se aplica filtro de modalidad.

**Ejemplo de Petición**:
```http
POST /api/v1/reporte-matricula/pdf/estadisticas
Content-Type: application/json

{
    "periodo_lectivo_id": 1,
    "fecha_inicio": "2024-01-01",
    "fecha_fin": "2024-03-31",
    "modalidad_id": "Todos"
}
```

**Respuesta**: Archivo PDF descargable con todas las estadísticas

---

### 3. Obtener Períodos Lectivos

**Ruta**: `GET /api/v1/reporte-matricula/periodos-lectivos`

**Descripción**: Obtiene la lista de todos los períodos lectivos disponibles.

**Parámetros**: Ninguno

**Ejemplo de Petición**:
```http
GET /api/v1/reporte-matricula/periodos-lectivos
```

**Ejemplo de Respuesta**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "2024-2025",
            "periodo_nota": true,
            "periodo_matricula": false
        },
        {
            "id": 2,
            "nombre": "2023-2024",
            "periodo_nota": false,
            "periodo_matricula": true
        }
    ],
    "message": "Períodos lectivos obtenidos correctamente"
}
```

---

### 4. Obtener Modalidades

**Ruta**: `GET /api/v1/reporte-matricula/modalidades`

**Descripción**: Obtiene la lista de modalidades disponibles para aplicar filtro en reportes.

**Parámetros**: Ninguno

**Ejemplo de Petición**:
```http
GET /api/v1/reporte-matricula/modalidades
```

**Ejemplo de Respuesta**:
```json
{
    "success": true,
    "data": [
        { "id": 1, "nombre": "Presencial" },
        { "id": 2, "nombre": "Virtual" }
    ],
    "message": "Modalidades obtenidas correctamente"
}
```

---

## Códigos de Respuesta HTTP

- **200 OK**: Operación exitosa
- **400 Bad Request**: Parámetros inválidos
- **401 Unauthorized**: No autenticado
- **403 Forbidden**: Sin permisos suficientes
- **422 Unprocessable Entity**: Errores de validación
- **500 Internal Server Error**: Error del servidor

---

## Rutas Registradas

```php
Route::prefix('reporte-matricula')->middleware('check.permissions:reportes.estadistica_matricula')->group(function () {
    Route::get('/estadisticas', [ReporteMatriculaController::class, 'estadisticas']);
    Route::post('/pdf/estadisticas', [ReporteMatriculaController::class, 'generarPdfEstadisticas']);
    Route::get('/periodos-lectivos', [ReporteMatriculaController::class, 'periodosLectivos']);
    Route::get('/modalidades', [ReporteMatriculaController::class, 'modalidades']);
});
```

---

## Notas Importantes

- Todos los endpoints requieren el permiso `reportes.estadistica_matricula`
- Las estadísticas consideran solo estudiantes con estado `ACTIVO` o `RETIRADO`
- Los géneros se clasifican como `M` (Masculino) y `F` (Femenino)
- El PDF se genera usando `wkhtmltopdf`
- Todas las operaciones son auditadas automáticamente