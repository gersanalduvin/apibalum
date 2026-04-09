# API Asignaturas

## Descripción
Gestión de asignaturas con CRUD completo, relación con área de asignaturas, búsqueda por `nombre`, paginación y exportación en PDF/Excel. Respuestas JSON en español y auditoría automática (evento `updated` por defecto).

## Permisos requeridos

| Acción | Permiso |
|-------|---------|
| Listar asignaturas | `not_materias.index` |
| Ver asignatura | `not_materias.show` |
| Crear asignatura | `not_materias.create` |
| Actualizar asignatura | `not_materias.update` |
| Eliminar asignatura | `not_materias.delete` |
| Exportar PDF | `not_materias.exportar_pdf` |
| Exportar Excel | `not_materias.exportar_excel` |

## Endpoints

### Listar (paginado)
**GET** `/api/v1/not-materias`

Parámetros de consulta:
- `per_page` (opcional, default: 15)
- `nombre` (opcional) texto de búsqueda

Respuesta (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      { "id": 1, "nombre": "Biología", "abreviatura": "BIO", "orden": 1, "materia_id": 3 }
    ],
    "per_page": 15,
    "total": 1
  },
  "message": "Materias obtenidas exitosamente"
}
```

### Crear asignatura
**POST** `/api/v1/not-materias`

Body:
```json
{
  "nombre": "Biología",
  "abreviatura": "BIO",
  "materia_id": 3,
  "orden": 1
}
```

Respuesta (201):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Biología", "abreviatura": "BIO", "materia_id": 3, "orden": 1 },
  "message": "Materia creada exitosamente"
}
```

### Ver asignatura
**GET** `/api/v1/not-materias/{id}`

Respuesta (200):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Biología", "abreviatura": "BIO", "materia_id": 3, "orden": 1 },
  "message": "Materia obtenida exitosamente"
}
```

### Actualizar asignatura
**PUT** `/api/v1/not-materias/{id}`

Body:
```json
{
  "nombre": "Biología I",
  "abreviatura": "BIO1",
  "materia_id": 3,
  "orden": 2
}
```

Respuesta (200):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Biología I", "abreviatura": "BIO1", "materia_id": 3, "orden": 2 },
  "message": "Materia actualizada exitosamente"
}
```

### Eliminar asignatura
**DELETE** `/api/v1/not-materias/{id}`

Respuesta (200):
```json
{
  "success": true,
  "data": null,
  "message": "Materia eliminada exitosamente"
}
```

### Exportar PDF
**GET** `/api/v1/not-materias/export/pdf`

Parámetros de consulta:
- `nombre` (opcional) texto de búsqueda

Devuelve archivo PDF descargable con el listado.

### Exportar Excel
**GET** `/api/v1/not-materias/export/excel`

Parámetros de consulta:
- `nombre` (opcional) texto de búsqueda

Devuelve archivo XLSX descargable con el listado.

## Validaciones
- `nombre`: requerido, string, máx 180
- `abreviatura`: requerido, string, máx 10
- `materia_id`: requerido, entero, existente en `not_materias_areas.id`
- `orden`: requerido, entero, 0–99

Errores de validación (422):
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": { "materia_id": ["El campo materia_id es obligatorio"] }
}
```

## Notas
- Autenticación: `auth:sanctum`
- Permisos: middleware `check.permissions:*` en cada ruta
- Auditoría: evento `updated` por defecto
- Respuestas: JSON en español

