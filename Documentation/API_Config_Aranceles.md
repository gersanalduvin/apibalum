# API Config Aranceles

## Descripción
API para la gestión de aranceles del sistema educativo. Permite crear, leer, actualizar y eliminar configuraciones de aranceles con soporte para sincronización offline.

## Permisos Requeridos

| Acción | Permiso |
|--------|---------|
| Listar aranceles | `config_aranceles.index` |
| Ver arancel específico | `config_aranceles.show` |
| Crear arancel | `config_aranceles.create` |
| Actualizar arancel | `config_aranceles.update` |
| Eliminar arancel | `config_aranceles.delete` |
| Buscar aranceles | `config_aranceles.search` |
| Sincronizar datos | `config_aranceles.sync` |

## Endpoints

### 1. Listar Aranceles (Paginado)
**GET** `/api/v1/config-aranceles`

**Permisos:** `config_aranceles.index`

**Parámetros de consulta:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15, max: 100)

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
                "codigo": "ARANCEL001",
                "nombre": "Matrícula Inicial",
                "precio": 150.00,
                "moneda": true,
                "activo": true,
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "first_page_url": "http://localhost/api/v1/config-aranceles?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/v1/config-aranceles?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/v1/config-aranceles",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "message": "Aranceles obtenidos exitosamente"
}
```

### 2. Obtener Todos los Aranceles (Sin paginación)
**GET** `/api/v1/config-aranceles/getall`

**Permisos:** `config_aranceles.index`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "ARANCEL001",
            "nombre": "Matrícula Inicial",
            "precio": 150.00,
            "moneda": true,
            "activo": true
        }
    ],
    "message": "Todos los aranceles obtenidos exitosamente"
}
```

### 3. Crear Arancel
**POST** `/api/v1/config-aranceles`

**Permisos:** `config_aranceles.create`

**Cuerpo de la petición:**
```json
{
    "codigo": "ARANCEL002",
    "nombre": "Mensualidad Básica",
    "precio": 200.50,
    "moneda": true,
    "cuenta_debito_id": 15,
    "cuenta_credito_id": 28,
    "activo": true
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "codigo": "ARANCEL002",
        "nombre": "Mensualidad Básica",
        "precio": 200.50,
        "moneda": true,
        "cuenta_debito_id": 15,
        "cuenta_credito_id": 28,
        "activo": true,
        "cuenta_debito": {
            "id": 15,
            "codigo": "1.1.01.001",
            "nombre": "Caja General"
        },
        "cuenta_credito": {
            "id": 28,
            "codigo": "4.1.01.001",
            "nombre": "Ingresos por Aranceles"
        },
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T11:00:00.000000Z"
    },
    "message": "Arancel creado exitosamente"
}
```

### 4. Ver Arancel por ID
**GET** `/api/v1/config-aranceles/{id}`

**Permisos:** `config_aranceles.show`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "codigo": "ARANCEL001",
        "nombre": "Matrícula Inicial",
        "precio": 150.00,
        "moneda": true,
        "activo": true,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    "message": "Arancel obtenido exitosamente"
}
```

### 5. Ver Arancel por UUID
**GET** `/api/v1/config-aranceles/uuid/{uuid}`

**Permisos:** `config_aranceles.show`

### 6. Ver Arancel por Código
**GET** `/api/v1/config-aranceles/codigo/{codigo}`

**Permisos:** `config_aranceles.show`

### 7. Actualizar Arancel por ID
**PUT** `/api/v1/config-aranceles/{id}`

**Permisos:** `config_aranceles.update`

**Cuerpo de la petición:**
```json
{
    "nombre": "Matrícula Inicial Actualizada",
    "precio": 175.00,
    "activo": true
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "codigo": "ARANCEL001",
        "nombre": "Matrícula Inicial Actualizada",
        "precio": 175.00,
        "moneda": true,
        "activo": true,
        "updated_at": "2024-01-15T12:00:00.000000Z"
    },
    "message": "Arancel actualizado exitosamente"
}
```

### 8. Actualizar Arancel por UUID
**PUT** `/api/v1/config-aranceles/uuid/{uuid}`

**Permisos:** `config_aranceles.update`

### 9. Eliminar Arancel por ID
**DELETE** `/api/v1/config-aranceles/{id}`

**Permisos:** `config_aranceles.delete`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Arancel eliminado exitosamente"
}
```

### 10. Eliminar Arancel por UUID
**DELETE** `/api/v1/config-aranceles/uuid/{uuid}`

**Permisos:** `config_aranceles.delete`

### 11. Buscar Aranceles
**GET** `/api/v1/config-aranceles/search`

**Permisos:** `config_aranceles.search`

**Parámetros de consulta:**
- `q` (opcional): Término de búsqueda (busca en código y nombre)
- `codigo` (opcional): Filtrar por código específico
- `nombre` (opcional): Filtrar por nombre
- `precio_min` (opcional): Precio mínimo
- `precio_max` (opcional): Precio máximo
- `moneda` (opcional): Filtrar por tipo de moneda (true/false)
- `activo` (opcional): Filtrar por estado activo (true/false)
- `page` (opcional): Número de página
- `per_page` (opcional): Elementos por página

**Ejemplo:**
```
GET /api/v1/config-aranceles/search?q=matricula&precio_min=100&precio_max=300&activo=true&per_page=10
```

### 12. Obtener Aranceles Activos
**GET** `/api/v1/config-aranceles/active`

**Permisos:** `config_aranceles.index`

### 13. Obtener Aranceles por Moneda
**GET** `/api/v1/config-aranceles/by-moneda`

**Permisos:** `config_aranceles.index`

**Parámetros de consulta:**
- `moneda` (requerido): true o false

### 14. Estadísticas de Aranceles
**GET** `/api/v1/config-aranceles/stats`

**Permisos:** `config_aranceles.index`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "total": 25,
        "activos": 20,
        "inactivos": 5,
        "por_moneda": {
            "local": 15,
            "extranjera": 10
        },
        "precio_promedio": 185.50,
        "precio_minimo": 50.00,
        "precio_maximo": 500.00
    },
    "message": "Estadísticas obtenidas exitosamente"
}
```

### 15. Obtener Catálogo de Cuentas
**GET** `/api/v1/config-aranceles/catalogo-cuentas`

**Permisos:** `config_aranceles.index`

**Descripción:** Obtiene todas las cuentas contables que permiten movimiento para ser utilizadas como cuentas débito y crédito en los aranceles.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "uuid": "550e8400-e29b-41d4-a716-446655440015",
            "codigo": "1.1.01.001",
            "nombre": "Caja General",
            "tipo": "activo",
            "nivel": 4,
            "naturaleza": "deudora",
            "permite_movimiento": true,
            "estado": "activo"
        },
        {
            "id": 28,
            "uuid": "550e8400-e29b-41d4-a716-446655440028",
            "codigo": "4.1.01.001",
            "nombre": "Ingresos por Aranceles",
            "tipo": "ingreso",
            "nivel": 4,
            "naturaleza": "acreedora",
            "permite_movimiento": true,
            "estado": "activo"
        }
    ],
    "message": "Catálogo de cuentas obtenido exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 16. Marcar como Sincronizados
**POST** `/api/v1/config-aranceles/mark-synced`

**Permisos:** `config_aranceles.sync`

**Cuerpo de la petición:**
```json
{
    "uuids": [
        "550e8400-e29b-41d4-a716-446655440000",
        "550e8400-e29b-41d4-a716-446655440001"
    ]
}
```

### 17. Obtener Actualizados Después de Fecha
**GET** `/api/v1/config-aranceles/updated-after`

**Permisos:** `config_aranceles.sync`

**Parámetros de consulta:**
- `updated_after` (requerido): Fecha en formato ISO 8601

### 18. Obtener No Sincronizados
**GET** `/api/v1/config-aranceles/not-synced`

**Permisos:** `config_aranceles.sync`

## Validaciones

### Campos de Arancel
- `codigo`: Requerido, string, máximo 50 caracteres, único
- `nombre`: Requerido, string, máximo 255 caracteres
- `precio`: Requerido, numérico, mayor a 0
- `moneda`: Requerido, string, valores: 'USD', 'PYG'
- `cuenta_debito_id`: Opcional, entero, debe existir en config_catalogo_cuentas
- `cuenta_credito_id`: Opcional, entero, debe existir en config_catalogo_cuentas, debe ser diferente de cuenta_debito_id
- `activo`: Opcional, booleano, por defecto true

### Reglas de Búsqueda
- `precio_min`: Numérico, mínimo 0
- `precio_max`: Numérico, mayor que precio_min
- `per_page`: Entero, entre 1 y 100

## Códigos de Error

- **400**: Datos de entrada inválidos
- **401**: No autenticado
- **403**: Sin permisos suficientes
- **404**: Arancel no encontrado
- **422**: Errores de validación
- **500**: Error interno del servidor

## Ejemplos de Errores

### Error de Validación (422)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "codigo": ["El código ya existe"],
        "precio": ["El precio debe ser mayor a 0"]
    }
}
```

### Error de Permisos (403)
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción"
}
```

### Arancel No Encontrado (404)
```json
{
    "success": false,
    "message": "Arancel no encontrado"
}
```

## Notas Adicionales

1. **UUID**: Todos los aranceles tienen un UUID único generado automáticamente
2. **Soft Delete**: Los aranceles eliminados se marcan como eliminados pero no se borran físicamente
3. **Auditoría**: Se registran los usuarios que crean, actualizan y eliminan aranceles
4. **Sincronización**: Soporte completo para modo offline con sincronización diferida
5. **Historial**: Se mantiene un historial de cambios en el campo `cambios`
6. **Moneda**: Campo booleano donde `false` = Córdoba, `true` = Dólar