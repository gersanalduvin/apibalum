# API de Inventario - Movimientos

## Descripción General
Esta documentación describe los endpoints disponibles para la gestión de movimientos de inventario del sistema. Los movimientos incluyen entradas, salidas, ajustes y transferencias de productos, generando automáticamente registros de kardex para mantener el control de stock.

## Base URL
```
/api/v1/movimientos-inventario
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo de inventario:

| Acción | Permiso Requerido |
|--------|-------------------|
| Listar movimientos | `inventario.movimientos.index` |
| Ver movimiento específico | `inventario.movimientos.show` |
| Crear movimiento | `inventario.movimientos.store` |
| Actualizar movimiento | `inventario.movimientos.update` |
| Eliminar movimiento | `inventario.movimientos.destroy` |
| Consultar por tipo | `inventario.movimientos.tipo` |
| Consultar por producto | `inventario.movimientos.producto` |
| Consultar por almacén | `inventario.movimientos.almacen` |
| Consultar por usuario | `inventario.movimientos.usuario` |
| Consultar por fechas | `inventario.movimientos.fechas` |
| Buscar movimientos | `inventario.movimientos.search` |
| Ver estadísticas | `inventario.movimientos.estadisticas` |
| Ver resumen de stock | `inventario.movimientos.stock` |
| Sincronización | `inventario.movimientos.sync` |
| Validar movimiento | `inventario.movimientos.validate` |

---

## Endpoints Disponibles

### 1. Listar Movimientos (Paginado)
**GET** `/api/v1/movimientos-inventario`

**Descripción:** Obtiene una lista paginada de movimientos de inventario con filtros opcionales.

**Permisos requeridos:** `inventario.movimientos.index`

**Parámetros de consulta:**
- `per_page` (opcional): Número de elementos por página (default: 15)
- `tipo_movimiento` (opcional): Filtrar por tipo (entrada, salida, ajuste_positivo, ajuste_negativo, transferencia)
- `producto_id` (opcional): Filtrar por ID de producto
- `almacen_id` (opcional): Filtrar por ID de almacén
- `usuario_id` (opcional): Filtrar por ID de usuario
- `fecha_desde` (opcional): Fecha de inicio (YYYY-MM-DD)
- `fecha_hasta` (opcional): Fecha de fin (YYYY-MM-DD)
- `estado` (opcional): Filtrar por estado (pendiente, procesado, cancelado)
- `moneda` (opcional): Filtrar por moneda (true=USD, false=NIO)

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
                "tipo_movimiento": "entrada",
                "cantidad": "10.0000",
                "costo_unitario": "25.5000",
                "valor_total": "255.0000",
                "stock_anterior": "0.0000",
                "stock_posterior": "10.0000",
                "almacen_id": 1,
                "usuario_id": 1,
                "moneda": false,
                "documento_tipo": "FACTURA",
                "documento_numero": "F001-001",
                "documento_fecha": "2025-01-15",
                "proveedor_id": 1,
                "cliente_id": null,
                "estado": "procesado",
                "observaciones": "Compra inicial de inventario",
                "fecha_movimiento": "2025-01-15",
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
                "almacen": {
                    "id": 1,
                    "nombre": "Almacén Principal"
                },
                "usuario": {
                    "id": 1,
                    "name": "Administrador",
                    "email": "admin@example.com"
                },
                "proveedor": {
                    "id": 1,
                    "nombre": "Proveedor ABC"
                }
            }
        ],
        "first_page_url": "http://localhost/api/v1/movimientos-inventario?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/v1/movimientos-inventario?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/v1/movimientos-inventario",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Movimientos obtenidos exitosamente"
}
```

### 2. Obtener Todos los Movimientos (Sin Paginación)
**GET** `/api/v1/movimientos-inventario/getall`

**Descripción:** Obtiene todos los movimientos de inventario sin paginación.

**Permisos requeridos:** `inventario.movimientos.index`

**Parámetros de consulta:** Los mismos que el endpoint paginado.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Todos los movimientos obtenidos exitosamente"
}
```

### 3. Crear Movimiento
**POST** `/api/v1/movimientos-inventario`

**Descripción:** Crea un nuevo movimiento de inventario y genera automáticamente el registro de kardex correspondiente.

**Permisos requeridos:** `inventario.movimientos.store`

**Cuerpo de la petición:**
```json
{
    "producto_id": 1,
    "tipo_movimiento": "entrada",
    "cantidad": "10.0000",
    "costo_unitario": "25.5000",
    "almacen_id": 1,
    "moneda": false,
    "documento_tipo": "FACTURA",
    "documento_numero": "F001-001",
    "documento_fecha": "2025-01-15",
    "proveedor_id": 1,
    "observaciones": "Compra inicial de inventario"
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "producto_id": 1,
        "tipo_movimiento": "entrada",
        "cantidad": "10.0000",
        "costo_unitario": "25.5000",
        "valor_total": "255.0000",
        "stock_posterior": "10.0000",
        "estado": "procesado",
        "created_at": "2025-01-15T10:30:00.000000Z"
    },
    "message": "Movimiento creado exitosamente"
}
```

### 4. Ver Movimiento Específico
**GET** `/api/v1/movimientos-inventario/{id}`

**Descripción:** Obtiene un movimiento específico por ID o UUID.

**Permisos requeridos:** `inventario.movimientos.show`

**Parámetros de ruta:**
- `id`: ID numérico o UUID del movimiento

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "producto_id": 1,
        "tipo_movimiento": "entrada",
        "cantidad": "10.0000",
        "costo_unitario": "25.5000",
        "valor_total": "255.0000",
        "stock_anterior": "0.0000",
        "stock_posterior": "10.0000",
        "observaciones": "Compra inicial",
        "producto": {
            "id": 1,
            "nombre": "Cuaderno Universitario 100 hojas",
            "codigo": "CU001"
        },
        "almacen": {
            "id": 1,
            "nombre": "Almacén Principal"
        },
        "kardex": [
            {
                "id": 1,
                "stock_posterior": "10.0000",
                "costo_promedio_posterior": "25.5000"
            }
        ]
    },
    "message": "Movimiento obtenido exitosamente"
}
```

### 5. Actualizar Movimiento
**PUT** `/api/v1/movimientos-inventario/{id}`

**Descripción:** Actualiza un movimiento existente y recalcula el kardex correspondiente.

**Permisos requeridos:** `inventario.movimientos.update`

**Cuerpo de la petición:**
```json
{
    "cantidad": "15.0000",
    "costo_unitario": "26.0000",
    "observaciones": "Cantidad corregida"
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "cantidad": "15.0000",
        "costo_unitario": "26.0000",
        "valor_total": "390.0000",
        "updated_at": "2025-01-15T11:00:00.000000Z"
    },
    "message": "Movimiento actualizado exitosamente"
}
```

### 6. Eliminar Movimiento
**DELETE** `/api/v1/movimientos-inventario/{id}`

**Descripción:** Elimina un movimiento (soft delete) y ajusta el kardex correspondiente.

**Permisos requeridos:** `inventario.movimientos.destroy`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Movimiento eliminado exitosamente"
}
```

### 7. Movimientos por Tipo
**GET** `/api/v1/movimientos-inventario/tipo/{tipo}`

**Descripción:** Obtiene movimientos filtrados por tipo específico.

**Permisos requeridos:** `inventario.movimientos.tipo`

**Parámetros de ruta:**
- `tipo`: Tipo de movimiento (entrada, salida, ajuste_positivo, ajuste_negativo, transferencia)

**Parámetros de consulta opcionales:**
- `fecha_desde`: Fecha de inicio
- `fecha_hasta`: Fecha de fin
- `producto_id`: Filtrar por producto

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "fecha_movimiento": "2025-01-15",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos por tipo obtenidos exitosamente"
}
```

### 8. Movimientos por Producto
**GET** `/api/v1/movimientos-inventario/producto/{producto_id}`

**Descripción:** Obtiene todos los movimientos de un producto específico.

**Permisos requeridos:** `inventario.movimientos.producto`

**Parámetros de consulta opcionales:**
- `fecha_desde`: Fecha de inicio
- `fecha_hasta`: Fecha de fin
- `tipo_movimiento`: Filtrar por tipo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "fecha_movimiento": "2025-01-15",
            "stock_posterior": "10.0000"
        }
    ],
    "message": "Movimientos del producto obtenidos exitosamente"
}
```

### 9. Movimientos por Almacén
**GET** `/api/v1/movimientos-inventario/almacen/{almacen_id}`

**Descripción:** Obtiene todos los movimientos de un almacén específico.

**Permisos requeridos:** `inventario.movimientos.almacen`

**Parámetros de consulta opcionales:**
- `fecha_desde`: Fecha de inicio
- `fecha_hasta`: Fecha de fin
- `tipo_movimiento`: Filtrar por tipo

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos del almacén obtenidos exitosamente"
}
```

### 10. Movimientos por Usuario
**GET** `/api/v1/movimientos-inventario/usuario/{usuario_id}`

**Descripción:** Obtiene todos los movimientos realizados por un usuario específico.

**Permisos requeridos:** `inventario.movimientos.usuario`

**Parámetros de consulta opcionales:**
- `fecha_desde`: Fecha de inicio
- `fecha_hasta`: Fecha de fin

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "fecha_movimiento": "2025-01-15",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos del usuario obtenidos exitosamente"
}
```

### 11. Movimientos por Rango de Fechas
**GET** `/api/v1/movimientos-inventario/fechas`

**Descripción:** Obtiene movimientos filtrados por rango de fechas.

**Permisos requeridos:** `inventario.movimientos.fechas`

**Parámetros de consulta (requeridos):**
- `fecha_desde`: Fecha de inicio (YYYY-MM-DD)
- `fecha_hasta`: Fecha de fin (YYYY-MM-DD)

**Parámetros opcionales:**
- `tipo_movimiento`: Filtrar por tipo
- `producto_id`: Filtrar por producto
- `almacen_id`: Filtrar por almacén

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "fecha_movimiento": "2025-01-15",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos por rango de fechas obtenidos exitosamente"
}
```

### 12. Buscar Movimientos
**GET** `/api/v1/movimientos-inventario/search`

**Descripción:** Busca movimientos por múltiples criterios.

**Permisos requeridos:** `inventario.movimientos.search`

**Parámetros de consulta:**
- `q` (opcional): Término de búsqueda general
- `numero_documento` (opcional): Buscar por número de documento
- `observaciones` (opcional): Buscar en observaciones
- `tipo_movimiento` (opcional): Filtrar por tipo
- `producto_nombre` (opcional): Buscar por nombre de producto

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "documento_numero": "F001-001",
            "observaciones": "Compra inicial",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Resultados de búsqueda obtenidos exitosamente"
}
```

### 13. Estadísticas de Movimientos
**GET** `/api/v1/movimientos-inventario/estadisticas`

**Descripción:** Obtiene estadísticas generales de movimientos de inventario.

**Permisos requeridos:** `inventario.movimientos.estadisticas`

**Parámetros de consulta opcionales:**
- `fecha_desde`: Fecha de inicio para el cálculo
- `fecha_hasta`: Fecha de fin para el cálculo
- `almacen_id`: Filtrar por almacén específico

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "total_movimientos": 150,
        "por_tipo": {
            "entrada": 75,
            "salida": 60,
            "ajuste_positivo": 10,
            "ajuste_negativo": 5
        },
        "valor_total": {
            "entradas": "15750.00",
            "salidas": "12300.00",
            "neto": "3450.00"
        },
        "productos_mas_movidos": [
            {
                "producto_id": 1,
                "nombre": "Cuaderno Universitario 100 hojas",
                "total_movimientos": 25
            }
        ],
        "periodo": {
            "fecha_desde": "2025-01-01",
            "fecha_hasta": "2025-01-31"
        }
    },
    "message": "Estadísticas obtenidas exitosamente"
}
```

### 14. Resumen de Stock
**GET** `/api/v1/movimientos-inventario/resumen-stock`

**Descripción:** Obtiene un resumen del stock actual basado en los movimientos.

**Permisos requeridos:** `inventario.movimientos.stock`

**Parámetros de consulta opcionales:**
- `almacen_id`: Filtrar por almacén específico
- `categoria_id`: Filtrar por categoría de producto
- `moneda`: Filtrar por moneda

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "producto_id": 1,
            "producto_nombre": "Cuaderno Universitario 100 hojas",
            "stock_actual": "45.0000",
            "valor_stock": "1147.50",
            "costo_promedio": "25.50",
            "ultimo_movimiento": "2025-01-15",
            "almacen": {
                "id": 1,
                "nombre": "Almacén Principal"
            }
        }
    ],
    "message": "Resumen de stock obtenido exitosamente"
}
```

### 15. Movimientos Recientes
**GET** `/api/v1/movimientos-inventario/recientes`

**Descripción:** Obtiene los movimientos más recientes con un límite específico.

**Permisos requeridos:** `inventario.movimientos.index`

**Parámetros de consulta opcionales:**
- `limite`: Número máximo de registros (default: 10, máximo: 50)
- `tipo_movimiento`: Filtrar por tipo específico

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "fecha_movimiento": "2025-01-15",
            "created_at": "2025-01-15T10:30:00.000000Z",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos recientes obtenidos exitosamente"
}
```

### 16. Sincronizar Movimientos
**POST** `/api/v1/movimientos-inventario/sync`

**Descripción:** Sincroniza movimientos pendientes desde clientes offline.

**Permisos requeridos:** `inventario.movimientos.sync`

**Cuerpo de la petición:**
```json
{
    "movimientos": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "producto_id": 1,
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "costo_unitario": "25.5000",
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
        "errores": 0,
        "detalles": [
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "estado": "sincronizado",
                "id_servidor": 1
            }
        ]
    },
    "message": "Sincronización completada exitosamente"
}
```

### 17. Buscar por Número de Documento
**GET** `/api/v1/movimientos-inventario/documento/{numero}`

**Descripción:** Busca movimientos por número de documento específico.

**Permisos requeridos:** `inventario.movimientos.search`

**Parámetros de ruta:**
- `numero`: Número de documento a buscar

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "documento_numero": "F001-001",
            "documento_tipo": "FACTURA",
            "tipo_movimiento": "entrada",
            "cantidad": "10.0000",
            "producto": {
                "nombre": "Cuaderno Universitario 100 hojas"
            }
        }
    ],
    "message": "Movimientos encontrados por número de documento"
}
```

### 18. Validar Movimiento
**POST** `/api/v1/movimientos-inventario/validate`

**Descripción:** Valida los datos de un movimiento antes de crearlo.

**Permisos requeridos:** `inventario.movimientos.validate`

**Cuerpo de la petición:**
```json
{
    "producto_id": 1,
    "tipo_movimiento": "salida",
    "cantidad": "5.0000",
    "almacen_id": 1
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "valido": true,
        "stock_disponible": "45.0000",
        "stock_resultante": "40.0000",
        "advertencias": []
    },
    "message": "Validación completada exitosamente"
}
```

**Respuesta con advertencias (200):**
```json
{
    "success": true,
    "data": {
        "valido": false,
        "stock_disponible": "2.0000",
        "stock_resultante": "-3.0000",
        "advertencias": [
            "Stock insuficiente para realizar la salida"
        ]
    },
    "message": "Validación completada con advertencias"
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
        "cantidad": ["La cantidad debe ser mayor a 0"]
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
    "message": "Movimiento no encontrado"
}
```

### 422 - Unprocessable Entity
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "producto_id": ["El producto seleccionado no existe"],
        "cantidad": ["El campo cantidad es obligatorio"]
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

## Validaciones de Campos

### Campos Requeridos para Crear Movimiento
- `producto_id`: ID del producto (debe existir)
- `tipo_movimiento`: Tipo de movimiento (entrada, salida, ajuste_positivo, ajuste_negativo, transferencia)
- `cantidad`: Cantidad del movimiento (mayor a 0)
- `costo_unitario`: Costo unitario (mayor a 0 para entradas)
- `almacen_id`: ID del almacén (debe existir)

### Campos Opcionales
- `moneda`: Moneda del movimiento (boolean, default: false)
- `documento_tipo`: Tipo de documento
- `documento_numero`: Número de documento
- `documento_fecha`: Fecha del documento
- `proveedor_id`: ID del proveedor (para entradas)
- `cliente_id`: ID del cliente (para salidas)
- `observaciones`: Observaciones del movimiento

### Reglas de Validación Especiales
1. **Salidas:** Verificar stock disponible antes de procesar
2. **Transferencias:** Requieren almacén origen y destino
3. **Ajustes:** Requieren justificación en observaciones
4. **Costos:** Obligatorios para entradas, opcionales para salidas

---

## Notas Importantes

1. **Generación Automática de Kardex:** Cada movimiento genera automáticamente un registro de kardex correspondiente.

2. **Control de Stock:** El sistema verifica automáticamente el stock disponible para movimientos de salida.

3. **Auditoría Completa:** Todos los movimientos incluyen campos de auditoría que se actualizan automáticamente.

4. **Soft Deletes:** Los movimientos eliminados se mantienen en la base de datos para preservar el historial.

5. **Transacciones:** Todas las operaciones de creación/actualización se realizan dentro de transacciones de base de datos.

6. **Monedas Múltiples:** El sistema maneja USD y NIO con kardex independiente para cada moneda.

7. **Middleware de Seguridad:** Todas las rutas están protegidas por autenticación y autorización.

8. **Sincronización Offline:** Soporte completo para sincronización de datos offline con resolución de conflictos.

9. **Relaciones Completas:** Los endpoints incluyen relaciones con productos, almacenes, usuarios, proveedores y clientes.

10. **Validaciones Avanzadas:** Validaciones de negocio específicas según el tipo de movimiento.

---

## Casos de Uso Comunes

### 1. Registrar Entrada de Mercancía
```
POST /api/v1/movimientos-inventario
{
    "producto_id": 1,
    "tipo_movimiento": "entrada",
    "cantidad": "50.0000",
    "costo_unitario": "25.00",
    "almacen_id": 1,
    "documento_tipo": "FACTURA",
    "documento_numero": "F001-001",
    "proveedor_id": 1
}
```

### 2. Registrar Salida de Producto
```
POST /api/v1/movimientos-inventario
{
    "producto_id": 1,
    "tipo_movimiento": "salida",
    "cantidad": "5.0000",
    "almacen_id": 1,
    "cliente_id": 1,
    "observaciones": "Venta al cliente"
}
```

### 3. Consultar Movimientos del Día
```
GET /api/v1/movimientos-inventario/fechas?fecha_desde=2025-01-15&fecha_hasta=2025-01-15
```

### 4. Ver Stock Actual
```
GET /api/v1/movimientos-inventario/resumen-stock?almacen_id=1
```

### 5. Buscar por Documento
```
GET /api/v1/movimientos-inventario/documento/F001-001
```