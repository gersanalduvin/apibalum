# API Áreas de Asignaturas

## Descripción
Gestión de áreas de asignaturas (clasificaciones) con CRUD completo, búsqueda por `nombre`, paginación y exportación en PDF/Excel. Respuestas JSON en español y auditoría automática (evento `updated` por defecto).

## Permisos requeridos

| Acción | Permiso |
|-------|---------|
| Listar áreas | `not_materias_areas.index` |
| Ver área | `not_materias_areas.show` |
| Crear área | `not_materias_areas.create` |
| Actualizar área | `not_materias_areas.update` |
| Eliminar área | `not_materias_areas.delete` |
| Exportar PDF | `not_materias_areas.exportar_pdf` |
| Exportar Excel | `not_materias_areas.exportar_excel` |

## Endpoints

### Listar (paginado)
**GET** `/api/v1/not-materias-areas`

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
      { "id": 1, "nombre": "Ciencias", "orden": 1 }
    ],
    "per_page": 15,
    "total": 1
  },
  "message": "Áreas obtenidas exitosamente"
}
```

### Crear área
**POST** `/api/v1/not-materias-areas`

Body:
```json
{
  "nombre": "Ciencias",
  "orden": 1
}
```

Respuesta (201):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Ciencias", "orden": 1 },
  "message": "Área creada exitosamente"
}
```

### Ver área
**GET** `/api/v1/not-materias-areas/{id}`

Respuesta (200):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Ciencias", "orden": 1 },
  "message": "Área obtenida exitosamente"
}
```

### Actualizar área
**PUT** `/api/v1/not-materias-areas/{id}`

Body:
```json
{
  "nombre": "Ciencias Naturales",
  "orden": 2
}
```

Respuesta (200):
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "Ciencias Naturales", "orden": 2 },
  "message": "Área actualizada exitosamente"
}
```

### Eliminar área
**DELETE** `/api/v1/not-materias-areas/{id}`

Respuesta (200):
```json
{
  "success": true,
  "data": null,
  "message": "Área eliminada exitosamente"
}
```

### Exportar PDF
**GET** `/api/v1/not-materias-areas/export/pdf`

Parámetros de consulta:
- `nombre` (opcional) texto de búsqueda

Devuelve archivo PDF descargable con el listado.

### Exportar Excel
**GET** `/api/v1/not-materias-areas/export/excel`

Parámetros de consulta:
- `nombre` (opcional) texto de búsqueda

Devuelve archivo XLSX descargable con el listado.

## Validaciones
- `nombre`: requerido, string, máx 180
- `orden`: requerido, entero, 0–99

Errores de validación (422):
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": { "nombre": ["El campo nombre es obligatorio"] }
}
```

## Notas
- Autenticación: `auth:sanctum`
- Permisos: middleware `check.permissions:*` en cada ruta
- Auditoría: evento `updated` por defecto
- Respuestas: JSON en español

