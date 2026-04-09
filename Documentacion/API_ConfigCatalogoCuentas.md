# API ConfigCatalogoCuentas

## Descripción
API para la gestión del catálogo de cuentas contables. Permite crear, consultar, actualizar y eliminar cuentas contables con estructura jerárquica, incluyendo funcionalidades específicas para filtros, búsquedas y sincronización offline.

## Permisos Requeridos

Todos los endpoints de esta API requieren autenticación y permisos específicos bajo la categoría `configuracion_academica`:

| Endpoint | Método | Permiso Requerido |
|----------|--------|-------------------|
| Listar cuentas (paginado) | GET | `config_catalogo_cuentas.index` |
| Listar todas las cuentas | GET | `config_catalogo_cuentas.index` |
| Crear cuenta | POST | `config_catalogo_cuentas.create` |
| Ver cuenta específica | GET | `config_catalogo_cuentas.show` |
| Actualizar cuenta | PUT | `config_catalogo_cuentas.update` |
| Eliminar cuenta | DELETE | `config_catalogo_cuentas.delete` |
| Filtrar cuentas | GET | `config_catalogo_cuentas.filter` |
| Buscar por código | GET | `config_catalogo_cuentas.show` |
| Estadísticas | GET | `config_catalogo_cuentas.index` |
| Sincronización | POST | `config_catalogo_cuentas.sync` |
| Cuentas no sincronizadas | GET | `config_catalogo_cuentas.sync` |
| Cuentas actualizadas | GET | `config_catalogo_cuentas.sync` |
| Marcar como sincronizada | POST | `config_catalogo_cuentas.sync` |

## Base URL
```
/api/v1/config-catalogo-cuentas
```

## Endpoints

### 1. Listar Cuentas (Paginado)
**GET** `/api/v1/config-catalogo-cuentas`

Obtiene una lista paginada de todas las cuentas del catálogo.

#### Parámetros de consulta:
- `per_page` (opcional): Número de registros por página (default: 15)

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "codigo": "1.1.01",
                "nombre": "Caja General",
                "tipo": "activo",
                "nivel": 3,
                "padre_id": 2,
                "es_grupo": false,
                "permite_movimiento": true,
                "naturaleza": "deudora",
                "descripcion": "Cuenta para manejo de efectivo en caja",
                "estado": "activo",
                "moneda_usd": false,
                "is_synced": true,
                "synced_at": "2024-01-15T10:30:00Z",
                "created_at": "2024-01-15T10:00:00Z",
                "updated_at": "2024-01-15T10:30:00Z",
                "padre": {
                    "id": 2,
                    "codigo": "1.1",
                    "nombre": "Activo Corriente"
                },
                "hijos": []
            }
        ],
        "per_page": 15,
        "total": 50
    },
    "message": "Cuentas obtenidas exitosamente"
}
```

### 2. Listar Todas las Cuentas
**GET** `/api/v1/config-catalogo-cuentas/getall`

Obtiene todas las cuentas sin paginación.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "1.1.01",
            "nombre": "Caja General",
            // ... resto de campos
        }
    ],
    "message": "Todas las cuentas obtenidas exitosamente"
}
```

### 3. Crear Cuenta
**POST** `/api/v1/config-catalogo-cuentas`

Crea una nueva cuenta en el catálogo.

#### Cuerpo de la petición:
```json
{
    "codigo": "1.1.02",
    "nombre": "Bancos",
    "tipo": "activo",
    "padre_id": 2,
    "es_grupo": false,
    "permite_movimiento": true,
    "naturaleza": "deudora",
    "descripcion": "Cuentas bancarias de la empresa",
    "estado": "activo",
    "moneda_usd": false
}
```

#### Campos requeridos:
- `codigo`: Código jerárquico único (formato: 1.1.01)
- `nombre`: Nombre descriptivo (máx. 150 caracteres)
- `tipo`: Tipo de cuenta (activo, pasivo, patrimonio, ingreso, gasto)
- `es_grupo`: Si es cuenta de grupo (true/false)
- `permite_movimiento`: Si permite movimientos contables (true/false)
- `naturaleza`: Naturaleza contable (deudora, acreedora)
- `estado`: Estado de la cuenta (activo, inactivo)
- `moneda_usd`: Tipo de moneda (false=Córdobas, true=Dólares)

#### Campos opcionales:
- `padre_id`: ID de la cuenta padre
- `descripcion`: Descripción detallada
- `nivel`: Se calcula automáticamente
- `uuid`: Se genera automáticamente si no se proporciona
- `version`: Para sincronización

#### Respuesta exitosa (201):
```json
{
    "success": true,
    "data": {
        "id": 3,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "codigo": "1.1.02",
        "nombre": "Bancos",
        // ... resto de campos
    },
    "message": "Cuenta creada exitosamente"
}
```

### 4. Ver Cuenta Específica
**GET** `/api/v1/config-catalogo-cuentas/{id}`

Obtiene los detalles de una cuenta específica.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "codigo": "1.1.01",
        "nombre": "Caja General",
        // ... resto de campos con relaciones
    },
    "message": "Cuenta obtenida exitosamente"
}
```

### 5. Actualizar Cuenta
**PUT** `/api/v1/config-catalogo-cuentas/{id}`

Actualiza una cuenta existente.

#### Cuerpo de la petición:
```json
{
    "nombre": "Caja General Actualizada",
    "descripcion": "Nueva descripción de la cuenta",
    "estado": "activo"
}
```

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Caja General Actualizada",
        // ... resto de campos actualizados
    },
    "message": "Cuenta actualizada exitosamente"
}
```

### 6. Eliminar Cuenta
**DELETE** `/api/v1/config-catalogo-cuentas/{id}`

Elimina una cuenta del catálogo (soft delete).

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": null,
    "message": "Cuenta eliminada exitosamente"
}
```

### 7. Filtrar Cuentas
**GET** `/api/v1/config-catalogo-cuentas/filtrar`

Filtra cuentas por diferentes criterios.

#### Parámetros de consulta:
- `filtro`: Tipo de filtro (tipo, nivel, naturaleza, moneda, grupo, movimiento, hijas, raiz, arbol, buscar)
- `valor`: Valor del filtro

#### Ejemplos:
- `/filtrar?filtro=tipo&valor=activo`
- `/filtrar?filtro=nivel&valor=2`
- `/filtrar?filtro=naturaleza&valor=deudora`
- `/filtrar?filtro=moneda&valor=true`
- `/filtrar?filtro=grupo`
- `/filtrar?filtro=movimiento`
- `/filtrar?filtro=hijas&valor=2`
- `/filtrar?filtro=raiz`
- `/filtrar?filtro=arbol`
- `/filtrar?filtro=buscar&valor=caja`

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "1.1.01",
            "nombre": "Caja General",
            // ... resto de campos
        }
    ],
    "message": "Cuentas filtradas por tipo exitosamente"
}
```

### 8. Buscar por Código
**GET** `/api/v1/config-catalogo-cuentas/codigo/{codigo}`

Busca una cuenta por su código específico.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "codigo": "1.1.01",
        "nombre": "Caja General",
        // ... resto de campos
    },
    "message": "Cuenta obtenida exitosamente"
}
```

### 9. Estadísticas
**GET** `/api/v1/config-catalogo-cuentas/estadisticas`

Obtiene estadísticas generales del catálogo de cuentas.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "total": 50,
        "activos": 45,
        "inactivos": 5,
        "grupos": 15,
        "movimiento": 35,
        "por_tipo": {
            "activo": 20,
            "pasivo": 10,
            "patrimonio": 5,
            "ingreso": 8,
            "gasto": 7
        },
        "por_naturaleza": {
            "deudora": 30,
            "acreedora": 20
        },
        "por_moneda": {
            "cordobas": 40,
            "dolares": 10
        }
    },
    "message": "Estadísticas obtenidas exitosamente"
}
```

## Endpoints de Sincronización (Modo Offline)

### 10. Sincronizar Cuentas
**POST** `/api/v1/config-catalogo-cuentas/sync`

Sincroniza cuentas desde el cliente al servidor.

#### Cuerpo de la petición:
```json
{
    "cuentas": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440002",
            "codigo": "1.1.03",
            "nombre": "Nueva Cuenta",
            "tipo": "activo",
            "version": 1,
            // ... resto de campos
        }
    ]
}
```

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": {
        "created": 2,
        "updated": 1,
        "errors": []
    },
    "message": "Sincronización completada"
}
```

### 11. Cuentas No Sincronizadas
**GET** `/api/v1/config-catalogo-cuentas/no-sincronizadas`

Obtiene cuentas que no han sido sincronizadas.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "is_synced": false,
            // ... resto de campos
        }
    ],
    "message": "Cuentas no sincronizadas obtenidas exitosamente"
}
```

### 12. Cuentas Actualizadas Después
**GET** `/api/v1/config-catalogo-cuentas/actualizadas-despues`

Obtiene cuentas actualizadas después de una fecha específica.

#### Parámetros de consulta:
- `fecha`: Fecha en formato ISO 8601 (2024-01-15T10:00:00Z)

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "updated_at": "2024-01-15T11:00:00Z",
            // ... resto de campos
        }
    ],
    "message": "Cuentas actualizadas obtenidas exitosamente"
}
```

### 13. Marcar como Sincronizada
**POST** `/api/v1/config-catalogo-cuentas/marcar-sincronizada`

Marca una cuenta como sincronizada.

#### Cuerpo de la petición:
```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": null,
    "message": "Cuenta marcada como sincronizada"
}
```

## Códigos de Respuesta

- **200**: Operación exitosa
- **201**: Recurso creado exitosamente
- **400**: Error en la petición (datos inválidos)
- **401**: No autenticado
- **403**: Sin permisos suficientes
- **404**: Recurso no encontrado
- **422**: Errores de validación
- **500**: Error interno del servidor

## Errores de Validación

### Ejemplo de respuesta de error (422):
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "codigo": ["El código ya existe."],
        "nombre": ["El nombre es obligatorio."],
        "tipo": ["El tipo debe ser: activo, pasivo, patrimonio, ingreso o gasto."]
    }
}
```

## Reglas de Negocio

1. **Códigos únicos**: Cada cuenta debe tener un código único en formato jerárquico (ej: 1.1.01)
2. **Jerarquía**: Las cuentas hijas deben tener el mismo tipo, naturaleza y moneda que su padre
3. **Cuentas de grupo**: No pueden permitir movimientos contables
4. **Cuentas de movimiento**: No pueden ser cuentas de grupo
5. **Eliminación**: No se pueden eliminar cuentas que tengan cuentas hijas
6. **Niveles**: Se calculan automáticamente basados en la jerarquía
7. **Referencias cíclicas**: No se permiten referencias circulares en la jerarquía

## Notas Adicionales

- Todos los endpoints requieren autenticación mediante Sanctum
- Los permisos se validan usando el middleware de permisos
- Las fechas se manejan en formato ISO 8601 UTC
- Los campos de sincronización solo son necesarios si se usa modo offline
- El sistema mantiene un historial de cambios en el campo `cambios` (JSON)
### 10. Árbol del Catálogo
**GET** `/api/v1/config-catalogo-cuentas/arbol`

Obtiene el catálogo de cuentas en formato jerárquico, comenzando desde las cuentas raíz, incluyendo sus hijos ordenados por `codigo`.

#### Respuesta exitosa (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "codigo": "1",
            "nombre": "Activo",
            "hijos": [
                {
                    "id": 2,
                    "codigo": "1.1",
                    "nombre": "Activo Corriente",
                    "hijos": [
                        {
                            "id": 3,
                            "codigo": "1.1.01",
                            "nombre": "Caja General",
                            "hijos": []
                        }
                    ]
                }
            ]
        }
    ],
    "message": "Árbol de cuentas obtenido exitosamente"
}
```