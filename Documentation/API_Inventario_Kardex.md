# API de Inventario - Kardex

## Descripción General
Esta documentación describe los endpoints disponibles para la consulta y gestión del kardex de inventario del sistema. El kardex se genera automáticamente a partir de los movimientos de inventario y proporciona un historial detallado de entradas, salidas y saldos de productos.

## Base URL
```
/api/v1/kardex
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo de inventario:

| Acción | Permiso Requerido |
|--------|-------------------|
| Consultar kardex general | `inventario.kardex.index` |
| Ver kardex específico | `inventario.kardex.show` |
| Consultar kardex por producto | `inventario.kardex.producto` |
| Consultar stock actual | `inventario.kardex.stock` |
| Consultar costo promedio | `inventario.kardex.costo` |
| Generar reportes de kardex | `inventario.kardex.reportes` |
| Consultar por fechas | `inventario.kardex.fechas` |
| Consultar por período | `inventario.kardex.periodo` |
| Sincronización | `inventario.kardex.sync` |

---

## Endpoints Disponibles

### 1. Consultar Kardex General (Paginado)
**GET** `/api/v1/kardex`

**Descripción:** Obtiene una lista paginada de registros de kardex con filtros opcionales.

**Permisos requeridos:** `inventario.kardex.index`

**Parámetros de consulta:**
- `per_page` (opcional): Número de elementos por página (default: 15)
- `producto_id` (opcional): Filtrar por ID de producto
- `tipo_movimiento` (opcional): Filtrar por tipo de movimiento (entrada, salida, ajuste_positivo, ajuste_negativo)
- `moneda` (opcional): Filtrar por moneda (true=USD, false=NIO)
- `fecha_desde` (opcional): Fecha de inicio (YYYY-MM-DD)
- `fecha_hasta` (opcional): Fecha de fin (YYYY-MM-DD)
- `activo` (opcional): Filtrar registros activos (true/false)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "producto_id": 1,
                "movimiento_id": 1,
                "tipo_movimiento": "entrada",
                "cantidad": "10.0000",
                "costo_unitario": "25.5000",
                "stock_anterior": "0.0000",
                "valor_anterior": "0.0000",
                "costo_promedio_anterior": "0.0000",
                "valor_movimiento": "255.0000",
                "stock_posterior": "10.0000",
                "valor_posterior": "255.0000",
                "costo_promedio_posterior": "25.5000",
                "moneda": false,
                "documento_tipo": "FACTURA",
                "documento_numero": "F001-001",
                "periodo_year": 2025,
                "periodo_month": 1,
                "fecha_movimiento": "2025-01-15",
                "activo": true,
                "es_ajuste_inicial": false,
                "es_cierre_periodo": false,
                "created_by": 1,
                "updated_by": 1,
                "deleted_by": null,
                "cambios": [
                    {
                        "accion": "creado",
                        "usuario": "admin@example.com",
                        "fecha": "2025-01-15 10:30:00",
                        "datos_anteriores": null,
                        "datos_nuevos": {...}
                    }
                ],
                "is_synced": true,
                "synced_at": "2025-01-15T10:30:00.000000Z",
                "updated_locally_at": null,
                "version": 1,
                "created_at": "2025-01-15T10:30:00.000000Z",
                "updated_at": "2025-01-15T10:30:00.000000Z",
                "deleted_at": null,
                "producto": {
                    "id": 1,
                    "nombre": "Cuaderno Universitario 100 hojas",
                    "codigo": "CU001"
                },
                "movimiento": {
                    "id": 1,
                    "numero_documento": "F001-001",
                    "observaciones": "Compra inicial"
                }
            }
        ],
        "first_page_url": "http://localhost/api/v1/kardex?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/v1/kardex?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/v1/kardex",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Registros de kardex obtenidos exitosamente"
}
```

### 2. Consultar Kardex por Producto
**GET** `/api/v1/kardex/producto/{producto_id}`

**Descripción:** Obtiene el historial completo de kardex para un producto específico.

**Permisos requeridos:** `inventario.kardex.producto`

**Parámetros de consulta:**
- `fecha_desde` (opcional): Fecha de inicio (YYYY-MM-DD)
- `fecha_hasta` (opcional): Fecha de fin (YYYY-MM-DD)
- `moneda` (opcional): Filtrar por moneda (true=USD, false=NIO)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "fecha_movimiento": "2025-01-15",
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "costo_unitario": "25.5000",
            "stock_anterior": "0.0000",
            "stock_posterior": "10.0000",
            "valor_movimiento": "255.0000",
            "documento_tipo": "FACTURA",
            "documento_numero": "F001-001",
            "producto": {
                "id": 1,
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Kardex del producto obtenido exitosamente"
}
```

### 3. Consultar Stock Actual
**GET** `/api/v1/kardex/stock/{producto_id}`

**Descripción:** Obtiene el stock actual de un producto basado en el último registro de kardex.

**Permisos requeridos:** `inventario.kardex.stock`

**Parámetros de consulta:**
- `moneda` (opcional): Especificar moneda (true=USD, false=NIO)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "producto_id": 1,
        "stock_actual": "45.0000",
        "moneda": false,
        "ultimo_movimiento": "2025-01-15",
        "producto": {
            "id": 1,
            "nombre": "Cuaderno Universitario 100 hojas",
            "codigo": "CU001"
        }
    },
    "message": "Stock actual obtenido exitosamente"
}
```

### 4. Consultar Costo Promedio Actual
**GET** `/api/v1/kardex/costo-promedio/{producto_id}`

**Descripción:** Obtiene el costo promedio actual de un producto basado en el último registro de kardex.

**Permisos requeridos:** `inventario.kardex.costo`

**Parámetros de consulta:**
- `moneda` (opcional): Especificar moneda (true=USD, false=NIO)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "producto_id": 1,
        "costo_promedio_actual": "25.5000",
        "moneda": false,
        "ultimo_movimiento": "2025-01-15",
        "producto": {
            "id": 1,
            "nombre": "Cuaderno Universitario 100 hojas",
            "codigo": "CU001"
        }
    },
    "message": "Costo promedio actual obtenido exitosamente"
}
```

### 5. Consultar Kardex por Rango de Fechas
**GET** `/api/v1/kardex/fechas`

**Descripción:** Obtiene registros de kardex filtrados por rango de fechas.

**Permisos requeridos:** `inventario.kardex.fechas`

**Parámetros de consulta (requeridos):**
- `fecha_desde`: Fecha de inicio (YYYY-MM-DD)
- `fecha_hasta`: Fecha de fin (YYYY-MM-DD)

**Parámetros opcionales:**
- `producto_id`: Filtrar por producto específico
- `tipo_movimiento`: Filtrar por tipo de movimiento
- `moneda`: Filtrar por moneda

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "fecha_movimiento": "2025-01-15",
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "stock_posterior": "10.0000",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Kardex por rango de fechas obtenido exitosamente"
}
```

### 6. Consultar Kardex por Período Contable
**GET** `/api/v1/kardex/periodo/{year}/{month?}`

**Descripción:** Obtiene registros de kardex de un período contable específico.

**Permisos requeridos:** `inventario.kardex.periodo`

**Parámetros de ruta:**
- `year`: Año del período (requerido)
- `month`: Mes del período (opcional, si no se especifica trae todo el año)

**Parámetros de consulta opcionales:**
- `producto_id`: Filtrar por producto específico
- `tipo_movimiento`: Filtrar por tipo de movimiento

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "periodo_year": 2025,
            "periodo_month": 1,
            "fecha_movimiento": "2025-01-15",
            "tipo_movimiento": "entrada",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Kardex del período obtenido exitosamente"
}
```

### 7. Generar Reporte de Kardex
**GET** `/api/v1/kardex/reporte/{producto_id}`

**Descripción:** Genera un reporte completo de kardex para un producto con resumen de movimientos.

**Permisos requeridos:** `inventario.kardex.reportes`

**Parámetros de consulta:**
- `fecha_desde` (opcional): Fecha de inicio
- `fecha_hasta` (opcional): Fecha de fin
- `formato` (opcional): Formato del reporte (json, excel, pdf)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "producto": {
            "id": 1,
            "nombre": "Cuaderno Universitario 100 hojas",
            "codigo": "CU001"
        },
        "periodo": {
            "fecha_desde": "2025-01-01",
            "fecha_hasta": "2025-01-31"
        },
        "resumen": {
            "stock_inicial": "0.0000",
            "total_entradas": "50.0000",
            "total_salidas": "5.0000",
            "stock_final": "45.0000",
            "valor_total": "1147.5000"
        },
        "movimientos": [
            {
                "fecha": "2025-01-15",
                "tipo": "entrada",
                "cantidad": "10.0000",
                "costo_unitario": "25.5000",
                "stock_resultante": "10.0000"
            }
        ]
    },
    "message": "Reporte de kardex generado exitosamente"
}
```

### 8. Sincronizar Kardex
**POST** `/api/v1/kardex/sync`

**Descripción:** Sincroniza registros de kardex pendientes (para modo offline).

**Permisos requeridos:** `inventario.kardex.sync`

**Cuerpo de la petición:**
```json
{
    "registros": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "producto_id": 1,
            "movimiento_id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "updated_locally_at": "2025-01-15T10:30:00.000000Z",
            "version": 1
        }
    ]
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "sincronizados": 1,
        "conflictos": 0,
        "errores": 0
    },
    "message": "Sincronización de kardex completada exitosamente"
}
```

---

## Respuestas de Error

### 400 - Bad Request
```json
{
    "success": false,
    "message": "Parámetros inválidos",
    "errors": {
        "fecha_desde": ["El campo fecha desde es requerido"]
    }
}
```

### 401 - Unauthorized
```json
{
    "success": false,
    "message": "No autenticado"
}
```

### 403 - Forbidden
```json
{
    "success": false,
    "message": "No tiene permisos para realizar esta acción"
}
```

### 404 - Not Found
```json
{
    "success": false,
    "message": "Producto no encontrado"
}
```

### 422 - Unprocessable Entity
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "producto_id": ["El producto seleccionado no existe"]
    }
}
```

### 500 - Internal Server Error
```json
{
    "success": false,
    "message": "Error interno del servidor"
}
```

---

## Notas Importantes

1. **Generación Automática:** Los registros de kardex se generan automáticamente cuando se crean movimientos de inventario a través del `MovimientoInventarioService`.

2. **Integridad de Datos:** El kardex mantiene la integridad de los saldos mediante cálculos automáticos de stock anterior, posterior y costos promedio.

3. **Auditoría:** Todos los registros incluyen campos de auditoría (`created_by`, `updated_by`, `deleted_by`, `cambios`) que se actualizan automáticamente.

4. **Soft Deletes:** Los registros eliminados se mantienen en la base de datos con `deleted_at` no nulo para preservar el historial.

5. **Monedas:** El sistema maneja dos monedas (USD=true, NIO=false) con kardex independiente para cada una.

6. **Períodos Contables:** Los registros se organizan por períodos contables (año/mes) para facilitar reportes y consultas.

7. **Middleware:** Todas las rutas están protegidas por autenticación Sanctum y verificación de permisos específicos.

8. **Sincronización:** El sistema incluye soporte para sincronización offline con campos `is_synced`, `synced_at` y `version`.

9. **Relaciones:** Los endpoints incluyen relaciones con productos y movimientos para proporcionar información completa.

10. **Filtros Avanzados:** Los endpoints soportan múltiples filtros para consultas específicas y reportes detallados.

---

## Casos de Uso Comunes

### 1. Consultar Historial de Producto
```
GET /api/v1/kardex/producto/1?fecha_desde=2025-01-01&fecha_hasta=2025-01-31
```

### 2. Verificar Stock Actual
```
GET /api/v1/kardex/stock/1?moneda=false
```

### 3. Generar Reporte Mensual
```
GET /api/v1/kardex/periodo/2025/1
```

### 4. Consultar Movimientos Recientes
```
GET /api/v1/kardex?fecha_desde=2025-01-15&per_page=10
```