# API de Recibos

## Descripción General
Este documento describe los endpoints del módulo de Recibos para creación, listado, anulación, impresión en PDF y reporte de montos.

## Base URL
`/api/v1/recibos`

## Autenticación
Todas las rutas requieren autenticación mediante token Sanctum.

## Permisos Requeridos
| Acción | Permiso |
|-------|---------|
| Listar recibos | `recibos.index` |
| Crear recibo | `recibos.store` |
| Eliminar recibo | `recibos.destroy` |
| Anular recibo | `recibos.anular` |
| Imprimir PDF | `recibos.imprimir` |
| Ver reporte de montos | `recibos.reporte` |

---

## Endpoints

### 1. Listar Recibos (Paginado)
**GET** `/api/v1/recibos`

Parámetros de consulta:
- `per_page` (opcional)
- `search` (opcional)
- `user_id` (opcional)
- `estado` (opcional: activo|anulado)
- `tipo` (opcional: interno|externo)
- `fecha_inicio` (opcional, date)
- `fecha_fin` (opcional, date)

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "numero_recibo": "R-0001",
        "tipo": "interno",
        "user_id": 10,
        "estado": "activo",
        "fecha": "2025-11-20",
        "nombre_usuario": "Juan Pérez",
        "total": 100.00,
        "grado": "5to",
        "seccion": "A",
        "tasa_cambio": 36.5000
      }
    ]
  },
  "message": "Recibos obtenidos exitosamente"
}
```

---

### 2. Crear Recibo
**POST** `/api/v1/recibos`

Cuerpo de la petición:
```json
{
  "numero_recibo": "R-0002",
  "tipo": "interno",
  "user_id": 10,
  "fecha": "2025-11-20",
  "nombre_usuario": "Juan Pérez",
  "grado": "5to",
  "seccion": "A",
  "detalles": [
    {
      "concepto": "Cuota escolar",
      "cantidad": 1,
      "monto": 100,
      "tipo_pago": "total",
      "aranceles_id": 3
    },
    {
      "concepto": "Libro",
      "cantidad": 2,
      "monto": 15.50,
      "producto_id": 5,
      "tipo_pago": "total"
    }
  ],
  "formas_pago": [
    { "forma_pago_id": 1, "monto": 100 },
    { "forma_pago_id": 2, "monto": 31 }
  ]
}
```

Notas:
- `tasa_cambio` se toma automáticamente de `config_parametros.tasa_cambio_dolar`.
- Si hay `producto_id` en detalles, se realiza salida de inventario.

Respuesta exitosa (201):
```json
{
  "success": true,
  "data": {
    "id": 2,
    "numero_recibo": "R-0002",
    "tipo": "interno",
    "estado": "activo",
    "fecha": "2025-11-20",
    "nombre_usuario": "Juan Pérez",
    "total": 131.00,
    "tasa_cambio": 36.5000,
    "detalles": [
      { "concepto": "Cuota escolar", "cantidad": 1, "monto": 100, "total": 100, "tipo_pago": "total" },
      { "concepto": "Libro", "cantidad": 2, "monto": 15.50, "total": 31, "tipo_pago": "total" }
    ],
    "formas_pago": [
      { "forma_pago_id": 1, "monto": 100 },
      { "forma_pago_id": 2, "monto": 31 }
    ]
  },
  "message": "Recibo creado exitosamente"
}
```

Errores de validación (422):
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "numero_recibo": ["El campo numero_recibo es obligatorio"],
    "detalles": ["Debe incluir al menos un detalle"]
  }
}
```

---

### 3. Eliminar Recibo
**DELETE** `/api/v1/recibos/{id}`

Respuesta exitosa (200):
```json
{ "success": true, "data": null, "message": "Recibo eliminado exitosamente" }
```

---

### 4. Anular Recibo
**PUT** `/api/v1/recibos/{id}/anular`

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": { "id": 2, "estado": "anulado" },
  "message": "Recibo anulado exitosamente"
}
```

---

### 5. Imprimir PDF del Recibo
**GET** `/api/v1/recibos/{id}/pdf`

Descripción: Genera y descarga un PDF del recibo usando la plantilla correspondiente según `tipo` (interno|externo).

Respuesta: Archivo PDF (cabeceras de descarga). En caso de error:
```json
{ "success": false, "message": "Error al generar el PDF", "errors": [] }
```

---

### 6. Reporte de Montos del Recibo
**GET** `/api/v1/recibos/{id}/reporte`

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": {
    "recibo": { "id": 2, "numero_recibo": "R-0002", "total": 131.00, "tasa_cambio": 36.5000 },
    "totales": { "total_detalles": 131.00, "total_cantidad": 3.00, "total_formas_pago": 131.00 },
    "por_tipo_pago": { "total": { "cantidad_items": 2, "total_montos": 131.00 } },
    "detalles": [ { "concepto": "Cuota escolar", "cantidad": 1, "monto": 100, "total": 100 } ],
    "formas_pago": [ { "forma_pago_id": 1, "monto": 100 }, { "forma_pago_id": 2, "monto": 31 } ]
  },
  "message": "Reporte de montos generado exitosamente"
}
```

---

### 7. Buscar Alumnos (Soporte a Recibos)
**GET** `/api/v1/recibos/alumnos/search`

Permiso requerido: `recibos.index`

Parámetros de consulta:
- `q` (opcional): texto de búsqueda por nombre, apellido, `email`, `codigo_mined`, `codigo_unico`
- `limit` (opcional): número máximo de resultados, 1–100 (default: 20)

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "primer_nombre": "Juan",
      "segundo_nombre": "Carlos",
      "primer_apellido": "Pérez",
      "segundo_apellido": "Gómez",
      "email": "juanperez@cempp.com",
      "codigo_mined": "MIN-001234",
      "codigo_unico": "ALU-00015",
      "tipo_usuario": "alumno"
    }
  ],
  "message": "Alumnos buscados exitosamente"
}
```

Errores de validación (400):
```json
{ "success": false, "message": "Error al buscar alumnos", "errors": [] }
```

---

### 8. Catálogo de Productos para Recibos
**GET** `/api/v1/recibos/catalogos/productos`

Permiso requerido: `recibos.index`

Parámetros de consulta:
- `q` (opcional): texto de búsqueda por nombre

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "codigo": "PROD005",
      "nombre": "Cuaderno rayado",
      "precio_venta": 35.50,
      "stock_actual": 120,
      "activo": true
    }
  ],
  "message": "Productos obtenidos exitosamente"
}
```

---

### 9. Catálogo de Aranceles para Recibos
**GET** `/api/v1/recibos/catalogos/aranceles`

Permiso requerido: `recibos.index`

Parámetros de consulta:
- `q` (opcional): texto de búsqueda general

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "codigo": "ARAN-COLEGIATURA",
      "nombre": "Colegiatura mensual",
      "monto": 500.00,
      "activo": true
    }
  ],
  "message": "Aranceles obtenidos exitosamente"
}
```

---

### 10. Formas de Pago
**GET** `/api/v1/recibos/catalogos/formas-pago`

Permiso requerido: `recibos.index`

Parámetros de consulta:
- `q` (opcional): texto de búsqueda por nombre o abreviatura

Respuesta exitosa (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Efectivo",
      "abreviatura": "EF",
      "activo": true
    }
  ],
  "message": "Formas de pago obtenidas exitosamente"
}
```

---

### Nota sobre Búsqueda de Alumnos con Aranceles Pendientes
El endpoint `GET /api/v1/recibos/alumnos/search` incluye, por cada alumno encontrado, el arreglo `arancelesPendientes` con sus aranceles en estado `pendiente` (campos: `id`, `rubro_id`, `aranceles_id`, `producto_id`, `importe_total`, `saldo_actual`, `estado`).

## Observaciones
- Los campos de auditoría (`created_by`, `updated_by`, `deleted_by`) se asignan automáticamente.
- La eliminación es soft delete.
- El inventario se actualiza con salida por cada detalle que incluya `producto_id`.
- Si un detalle incluye `rubro_id`, se actualiza `users_aranceles` del alumno: se incrementa `saldo_pagado`, se recalcula `saldo_actual` y se ajusta `estado` a `pagado` si el saldo queda en 0.

## Códigos de Respuesta
- 200 OK
- 201 Created
- 400 Bad Request
- 404 Not Found
- 422 Unprocessable Entity
- 500 Internal Server Error