# API Config Escala de Notas

## Descripción
Gestión de escalas de notas y sus detalles dentro del módulo de configuración académica. Incluye listado paginado con búsqueda por parámetro `notas`, creación/actualización conjunta de escala y detalles, eliminación y exportación a PDF/Excel.

## Permisos Requeridos

| Acción | Permiso |
|--------|---------|
| Ver escalas | `config_not_escala.index` |
| Crear escala | `config_not_escala.create` |
| Actualizar escala | `config_not_escala.update` |
| Eliminar escala | `config_not_escala.delete` |

## Endpoints

### 1. Listar Escalas (Paginado)
**GET** `/api/v1/config-not-escala`

**Permisos:** `config_not_escala.index`

**Parámetros de consulta:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15)
- `notas` (opcional): Texto para buscar en nombre de escala y detalles (nombre/abreviatura)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "uuid": "...",
        "nombre": "Escala 100",
        "detalles": [
          {"id": 10, "nombre": "Excelente", "abreviatura": "EXC", "rango_inicio": 90, "rango_fin": 100, "orden": 1},
          {"id": 11, "nombre": "Bueno", "abreviatura": "BUE", "rango_inicio": 80, "rango_fin": 89, "orden": 2}
        ]
      }
    ],
    "per_page": 15,
    "total": 1
  },
  "message": "Escalas obtenidas exitosamente"
}
```

### 2. Crear/Actualizar Escala con Detalles
**POST** `/api/v1/config-not-escala`

**Permisos:** `config_not_escala.create`

Este endpoint permite crear una nueva escala y, al mismo tiempo, agregar o actualizar sus detalles. Si incluye `id` en el cuerpo, actualiza la escala existente y procesa los detalles como upsert (actualiza si incluyen `id`, crea si no).

**Body (JSON):**
```json
{
  "id": 1,
  "nombre": "Escala 100",
  "detalles": [
    {"id": 10, "nombre": "Excelente", "abreviatura": "EXC", "rango_inicio": 90, "rango_fin": 100, "orden": 1},
    {"nombre": "Bueno", "abreviatura": "BUE", "rango_inicio": 80, "rango_fin": 89, "orden": 2}
  ]
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "...",
    "nombre": "Escala 100",
    "detalles": [
      {"id": 10, "nombre": "Excelente", "abreviatura": "EXC", "rango_inicio": 90, "rango_fin": 100, "orden": 1},
      {"id": 12, "nombre": "Bueno", "abreviatura": "BUE", "rango_inicio": 80, "rango_fin": 89, "orden": 2}
    ]
  },
  "message": "Escala y detalles guardados exitosamente"
}
```

### 3. Eliminar Escala
**DELETE** `/api/v1/config-not-escala/{id}`

**Permisos:** `config_not_escala.delete`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Escala eliminada exitosamente"
}
```

### 4. Eliminar Detalle de Escala
**DELETE** `/api/v1/config-not-escala/detalle/{id}`

**Permisos:** `config_not_escala.delete`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Detalle de escala eliminado exitosamente"
}
```

### 5. Exportar a PDF
**GET** `/api/v1/config-not-escala/export/pdf`

**Permisos:** `config_not_escala.index`

**Parámetros de consulta:**
- `notas` (opcional): filtro aplicado en la exportación

Devuelve un archivo PDF descargable con el listado de escalas y sus detalles.

### 6. Exportar a Excel
**GET** `/api/v1/config-not-escala/export/excel`

**Permisos:** `config_not_escala.index`

**Parámetros de consulta:**
- `notas` (opcional): filtro aplicado en la exportación

Devuelve un archivo XLSX descargable con el listado de escalas y sus detalles.

## Consideraciones
- Todas las respuestas son JSON y los mensajes están en español.
- Se aplica auditoría automática (`Auditable`) y borrado lógico (`softDeletes`).
- Se incluyen campos de sincronización para compatibilidad con modo offline.

