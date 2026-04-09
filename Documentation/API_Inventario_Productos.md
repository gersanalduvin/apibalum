# API de Inventario - Productos

## Descripción General
Esta documentación describe los endpoints disponibles para la gestión de productos en el módulo de inventario del sistema.

## Base URL
```
/api/v1/productos
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token.

## Permisos Requeridos
Cada endpoint requiere permisos específicos del módulo de inventario:

| Acción | Permiso Requerido |
|--------|-------------------|
| Listar productos | `inventario.productos.index` |
| Ver producto específico | `inventario.productos.show` |
| Crear producto | `inventario.productos.create` |
| Actualizar producto | `inventario.productos.update` |
| Eliminar producto | `inventario.productos.delete` |
| Buscar productos | `inventario.productos.search` |
| Gestionar stock | `inventario.productos.stock` |
| Sincronización | `inventario.productos.sync` |

---

## Endpoints Disponibles

### 1. Listar Productos (Paginado)
**GET** `/api/v1/productos`

**Descripción:** Obtiene una lista paginada de productos.

**Parámetros de consulta:**
- `per_page` (opcional): Número de elementos por página (default: 15)

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
                "codigo": "PROD001",
                "nombre": "Producto Ejemplo",
                "descripcion": "Descripción del producto",
                "categoria_id": 1,
                "unidad_medida": "UND",
                "precio_venta": 15.75,
                "stock_minimo": 5,
                "stock_maximo": 100,
                "stock_actual": 50,
                "costo_promedio": 10.50,
                "moneda": false,
                "cuenta_inventario_id": 1,
                "cuenta_costo_id": 2,
                "cuenta_venta_id": 3,
                "activo": true,
                "is_synced": true,
                "synced_at": "2024-01-15T10:30:00Z",
                "updated_locally_at": "2024-01-15T10:30:00Z",
                "version": 1,
                "created_by": 1,
                "updated_by": null,
                "deleted_by": null,
                "created_at": "2024-01-15T10:30:00Z",
                "updated_at": "2024-01-15T10:30:00Z",
                "deleted_at": null
            }
        ],
        "first_page_url": "http://localhost:8000/api/v1/productos?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/v1/productos?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost:8000/api/v1/productos",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Productos obtenidos exitosamente"
}
```

---

### 2. Listar Todos los Productos (Sin Paginación)
**GET** `/api/v1/productos/getall`

**Descripción:** Obtiene todos los productos sin paginación.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "PROD001",
            "nombre": "Producto Ejemplo",
            "categoria_id": 1,
            // ... resto de campos
        }
    ],
    "message": "Productos obtenidos exitosamente"
}
```

---

### 3. Crear Producto
**POST** `/api/v1/productos`

**Descripción:** Crea un nuevo producto en el inventario.

**Cuerpo de la petición:**
```json
{
    "codigo": "PROD002",
    "nombre": "Nuevo Producto",
    "descripcion": "Descripción del nuevo producto",
    "categoria_id": 1,
    "unidad_medida": "KG",
    "precio_venta": 18.00,
    "stock_minimo": 10,
    "stock_maximo": 200,
    "stock_actual": 100,
    "costo_promedio": 12.00,
    "moneda": false,
    "cuenta_inventario_id": 1,
    "cuenta_costo_id": 2,
    "cuenta_venta_id": 3,
    "activo": true
}
```

### Validaciones de Campos

| Campo | Validaciones |
|-------|--------------|
| `codigo` | Requerido, string, máximo 50 caracteres, único |
| `nombre` | Requerido, string, máximo 255 caracteres |
| `descripcion` | Opcional, string, máximo 1000 caracteres |
| `categoria_id` | Opcional, entero, debe existir en tabla inventario_categorias |
| `unidad_medida` | Requerido, enum: UND, KG, GR, LB, OZ, LT, ML, GL, M, CM, MM, IN, FT, M2, M3, PAR, DOC, CEN, MIL, CAJ, PAQ, BOL, SAC, TAM, BAR, ROL, PLI, JGO, SET, KIT, LOT, SRV, HOR, DIA, MES, AÑO |
| `precio_venta` | Requerido, numérico, mínimo 0, máximo 2 decimales |
| `stock_minimo` | Requerido, entero, mínimo 0 |
| `stock_maximo` | Requerido, entero, mínimo 0, mayor que stock_minimo |
| `stock_actual` | Requerido, entero, mínimo 0 |
| `costo_promedio` | Requerido, numérico, mínimo 0, máximo 2 decimales |
| `moneda` | Requerido, booleano (false = Córdoba, true = Dólar) |
| `cuenta_inventario_id` | Requerido, entero, debe existir en tabla cuentas |
| `cuenta_costo_id` | Requerido, entero, debe existir en tabla cuentas |
| `cuenta_venta_id` | Requerido, entero, debe existir en tabla cuentas |
| `activo` | Opcional, booleano, por defecto true |

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "codigo": "PROD002",
        "nombre": "Nuevo Producto",
        "descripcion": "Descripción del nuevo producto",
        "categoria_id": 1,
        "unidad_medida": "KG",
        "precio_venta": 18.00,
        "stock_minimo": 10,
        "stock_maximo": 200,
        "stock_actual": 100,
        "costo_promedio": 12.00,
        "moneda": false,
        "cuenta_inventario_id": 1,
        "cuenta_costo_id": 2,
        "cuenta_venta_id": 3,
        "activo": true,
        "is_synced": true,
        "synced_at": "2024-01-15T11:00:00Z",
        "updated_locally_at": "2024-01-15T11:00:00Z",
        "version": 1,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "created_at": "2024-01-15T11:00:00Z",
        "updated_at": "2024-01-15T11:00:00Z",
        "deleted_at": null,
        "cambios": []
    },
    "message": "Producto creado exitosamente"
}
```

---

### 4. Ver Producto Específico
**GET** `/api/v1/productos/{id}`

**Descripción:** Obtiene los detalles de un producto específico.

**Parámetros de ruta:**
- `id`: ID del producto

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "codigo": "PROD001",
        "nombre": "Producto Ejemplo",
        "descripcion": "Descripción del producto",
        "categoria_id": 1,
        "unidad_medida": "UND",
        "precio_venta": 15.75,
        "stock_minimo": 5,
        "stock_maximo": 100,
        "stock_actual": 50,
        "costo_promedio": 10.50,
        "moneda": false,
        "cuenta_inventario_id": 1,
        "cuenta_costo_id": 2,
        "cuenta_venta_id": 3,
        "activo": true,
        "is_synced": true,
        "synced_at": "2024-01-15T10:30:00Z",
        "updated_locally_at": "2024-01-15T10:30:00Z",
        "version": 1,
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z",
        "deleted_at": null,
        "cambios": []
    },
    "message": "Producto obtenido exitosamente"
}
```

**Respuesta de error (404):**
```json
{
    "success": false,
    "message": "Producto no encontrado",
    "errors": []
}
```

---

### 5. Actualizar Producto
**PUT** `/api/v1/productos/{id}`

**Descripción:** Actualiza un producto existente.

**Parámetros de ruta:**
- `id`: ID del producto

**Cuerpo de la petición:** (Misma estructura que crear producto)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "codigo": "PROD001",
        "nombre": "Producto Actualizado",
        "descripcion": "Descripción actualizada del producto",
        "categoria_id": 2,
        "unidad_medida": "KG",
        "precio_venta": 20.00,
        "stock_minimo": 8,
        "stock_maximo": 150,
        "stock_actual": 75,
        "costo_promedio": 12.50,
        "moneda": true,
        "cuenta_inventario_id": 1,
        "cuenta_costo_id": 2,
        "cuenta_venta_id": 3,
        "activo": true,
        "is_synced": false,
        "synced_at": "2024-01-15T10:30:00Z",
        "updated_locally_at": "2024-01-15T12:00:00Z",
        "version": 2,
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T12:00:00Z",
        "deleted_at": null,
        "cambios": [
            {
                "campo": "precio_venta",
                "valor_anterior": 15.75,
                "valor_nuevo": 20.00,
                "usuario": "admin@example.com",
                "fecha": "2024-01-15T12:00:00Z"
            }
        ]
    },
    "message": "Producto actualizado exitosamente"
}
```

---

### 6. Eliminar Producto
**DELETE** `/api/v1/productos/{id}`

**Descripción:** Elimina un producto (soft delete).

**Parámetros de ruta:**
- `id`: ID del producto

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Producto eliminado exitosamente"
}
```

---

### 7. Buscar Productos por Código
**GET** `/api/v1/productos/buscar/codigo`

**Descripción:** Busca productos por código.

**Parámetros de consulta:**
- `codigo`: Código del producto a buscar

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "PROD001",
            "nombre": "Producto Ejemplo",
            // ... resto de campos
        }
    ],
    "message": "Búsqueda por código completada"
}
```

---

### 8. Buscar Productos por Nombre
**GET** `/api/v1/productos/buscar/nombre`

**Descripción:** Busca productos por nombre.

**Parámetros de consulta:**
- `nombre`: Nombre del producto a buscar

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "PROD001",
            "nombre": "Producto Ejemplo",
            // ... resto de campos
        }
    ],
    "message": "Búsqueda por nombre completada"
}
```

---

### 9. Productos con Stock Bajo
**GET** `/api/v1/productos/stock/bajo`

**Descripción:** Obtiene productos con stock por debajo del mínimo.

**Parámetros de consulta:**
- `stock_minimo` (opcional): Umbral de stock mínimo (default: 10)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "PROD001",
            "nombre": "Producto con Stock Bajo",
            "stock_actual": 3,
            "stock_minimo": 5,
            // ... resto de campos
        }
    ],
    "message": "Productos con stock bajo obtenidos exitosamente"
}
```

---

### 10. Actualizar Stock de Producto
**PUT** `/api/v1/productos/{id}/stock`

**Descripción:** Actualiza el stock de un producto específico.

**Parámetros de ruta:**
- `id`: ID del producto

**Cuerpo de la petición:**
```json
{
    "stock": 75,
    "motivo": "Ajuste por inventario físico"
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "codigo": "PROD001",
        "nombre": "Producto Ejemplo",
        "stock_actual": 75,
        // ... resto de campos
        "updated_at": "2024-01-15T13:00:00Z"
    },
    "message": "Stock actualizado exitosamente"
}
```

---

### 11. Productos Activos
**GET** `/api/v1/productos/estado/activos`

**Descripción:** Obtiene todos los productos con estado "activo".

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "PROD001",
            "nombre": "Producto Activo",
            "estado": "activo",
            // ... resto de campos
        }
    ],
    "message": "Productos activos obtenidos exitosamente"
}
```

---

## Endpoints de Sincronización (Modo Offline)

### 12. Productos No Sincronizados
**GET** `/api/v1/productos/sync/no-sincronizados`

**Descripción:** Obtiene productos que no han sido sincronizados con el servidor.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "PROD001",
            "is_synced": false,
            "updated_locally_at": "2024-01-15T14:00:00Z",
            // ... resto de campos
        }
    ],
    "message": "Productos no sincronizados obtenidos exitosamente"
}
```

---

### 13. Marcar Producto como Sincronizado
**POST** `/api/v1/productos/sync/marcar-sincronizado`

**Descripción:** Marca un producto como sincronizado.

**Cuerpo de la petición:**
```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Producto marcado como sincronizado"
}
```

---

### 14. Productos Actualizados Después de Fecha
**GET** `/api/v1/productos/sync/actualizados-despues`

**Descripción:** Obtiene productos actualizados después de una fecha específica.

**Parámetros de consulta:**
- `fecha`: Fecha en formato ISO 8601 (YYYY-MM-DDTHH:MM:SSZ)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "PROD001",
            "updated_at": "2024-01-15T15:00:00Z",
            // ... resto de campos
        }
    ],
    "message": "Productos actualizados obtenidos exitosamente"
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 201 | Created - Recurso creado exitosamente |
| 400 | Bad Request - Error en la petición |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Errores de validación |
| 500 | Internal Server Error - Error interno del servidor |

---

## Estructura de Errores de Validación

```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "codigo": ["El código del producto es obligatorio"],
        "precio_venta": ["El precio de venta debe ser mayor al precio de compra"],
        "stock_maximo": ["El stock máximo debe ser mayor al stock mínimo"]
    }
}
```

---

## Notas Importantes

### Campos de Auditoría
- `created_by`, `updated_by`, `deleted_by`: Se asignan automáticamente según el usuario autenticado
- `created_at`, `updated_at`, `deleted_at`: Timestamps automáticos de Laravel

### Historial de Cambios
- El campo `cambios` registra automáticamente todas las modificaciones
- Estructura: `[{"campo": "nombre_campo", "valor_anterior": "valor", "valor_nuevo": "valor", "usuario": "email", "fecha": "timestamp"}]`
- Se registra en creación, actualización y eliminación

### Sincronización (Modo Offline)
- `uuid`: Identificador único universal para sincronización
- `is_synced`: Indica si el registro está sincronizado con el servidor
- `synced_at`: Timestamp de última sincronización exitosa
- `updated_locally_at`: Timestamp de última modificación local
- `version`: Control de versiones para resolución de conflictos

### Soft Delete
- Los productos eliminados no se borran físicamente
- Se marca `deleted_at` con timestamp de eliminación
- Se puede recuperar el registro si es necesario

### Validaciones Personalizadas
- El precio de venta debe ser mayor al costo promedio
- El stock máximo debe ser mayor al stock mínimo
- Las cuentas contables deben existir y estar activas
- El código debe ser único entre productos activos

### Unidades de Medida Disponibles
Los siguientes códigos están disponibles para el campo `unidad_medida`:

**Unidades básicas:**
- `UND` - Unidad
- `PAR` - Par
- `DOC` - Docena
- `CEN` - Centena
- `MIL` - Millar

**Peso:**
- `KG` - Kilogramo
- `GR` - Gramo
- `LB` - Libra
- `OZ` - Onza

**Volumen:**
- `LT` - Litro
- `ML` - Mililitro
- `GL` - Galón

**Longitud:**
- `M` - Metro
- `CM` - Centímetro
- `MM` - Milímetro
- `IN` - Pulgada
- `FT` - Pie

**Área y Volumen:**
- `M2` - Metro cuadrado
- `M3` - Metro cúbico

**Contenedores:**
- `CAJ` - Caja
- `PAQ` - Paquete
- `BOL` - Bolsa
- `SAC` - Saco
- `TAM` - Tambor
- `BAR` - Barril
- `ROL` - Rollo
- `PLI` - Pliego

**Conjuntos:**
- `JGO` - Juego
- `SET` - Set
- `KIT` - Kit
- `LOT` - Lote

**Servicios y Tiempo:**
- `SRV` - Servicio
- `HOR` - Hora
- `DIA` - Día
- `MES` - Mes
- `AÑO` - Año

### Monedas Disponibles

El campo `moneda` es de tipo booleano y representa:

- `false` - Córdoba (moneda local)
- `true` - Dólar (moneda extranjera)

---

## Endpoints de Datos Relacionados

### 15. Obtener Categorías de Productos
**GET** `/api/v1/productos/categorias`

**Descripción:** Obtiene todas las categorías activas de productos del inventario con sus relaciones jerárquicas.

**Permisos requeridos:** `inventario.productos.index`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "CAT001",
            "nombre": "Electrónicos",
            "descripcion": "Productos electrónicos y tecnológicos",
            "categoria_padre_id": null,
            "activo": true,
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-15T10:30:00Z",
            "deleted_at": null,
            "categoria_padre": null,
            "categorias_hijas": [
                {
                    "id": 2,
                    "codigo": "CAT002",
                    "nombre": "Smartphones",
                    "categoria_padre_id": 1,
                    "activo": true
                }
            ]
        }
    ],
    "message": "Categorías obtenidas exitosamente"
}
```

**Respuesta de error (401):**
```json
{
    "success": false,
    "message": "No autorizado",
    "errors": []
}
```

**Respuesta de error (403):**
```json
{
    "success": false,
    "message": "No tiene permisos para realizar esta acción",
    "errors": []
}
```

**Respuesta de error (500):**
```json
{
    "success": false,
    "message": "Error interno del servidor",
    "errors": []
}
```

---

### 16. Obtener Catálogo de Cuentas
**GET** `/api/v1/productos/catalogo-cuentas`

**Descripción:** Obtiene todas las cuentas activas del catálogo contable con sus relaciones jerárquicas.

**Permisos requeridos:** `inventario.productos.index`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "1.1.01.001",
            "nombre": "Inventario de Mercaderías",
            "descripcion": "Cuenta para el control de inventarios",
            "tipo": "activo",
            "grupo": "activo_corriente",
            "naturaleza": "deudor",
            "acepta_movimiento": true,
            "nivel": 4,
            "padre_id": 2,
            "activo": true,
            "created_by": 1,
            "updated_by": null,
            "deleted_by": null,
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-15T10:30:00Z",
            "deleted_at": null,
            "padre": {
                "id": 2,
                "codigo": "1.1.01",
                "nombre": "Inventarios",
                "nivel": 3
            },
            "hijos": []
        }
    ],
    "message": "Catálogo de cuentas obtenido exitosamente"
}
```

**Respuesta de error (401):**
```json
{
    "success": false,
    "message": "No autorizado",
    "errors": []
}
```

**Respuesta de error (403):**
```json
{
    "success": false,
    "message": "No tiene permisos para realizar esta acción",
    "errors": []
}
```

**Respuesta de error (500):**
```json
{
    "success": false,
    "message": "Error interno del servidor",
    "errors": []
}
```

---

### Permisos
- Cada endpoint requiere permisos específicos del módulo de inventario
- Asegúrese de que el usuario tenga los permisos necesarios antes de realizar las operaciones