# API Users Aranceles - Documentación

## Descripción General

La API de **Users Aranceles** permite gestionar los pagos y aranceles de los alumnos del sistema educativo. Proporciona funcionalidades para crear, consultar, actualizar y eliminar registros de aranceles, así como operaciones específicas como aplicar planes de pago, procesar pagos, exonerar y quitar recargos.

## Estructura de la Tabla

### Tabla: `users_aranceles`

| Campo | Tipo | Descripción | Valor por Defecto |
|-------|------|-------------|-------------------|
| `id` | bigint | Identificador único | AUTO_INCREMENT |
| `rubro_id` | bigint | Relación con config_plan_pago_detalle | null |
| `user_id` | bigint | Relación con users (alumno) | null |
| `aranceles_id` | bigint | Relación con config_aranceles | null |
| `producto_id` | bigint | Relación con inventario_producto | null |
| `importe` | decimal(10,2) | Importe base | 0.00 |
| `beca` | decimal(10,2) | Monto de beca aplicada | 0.00 |
| `descuento` | decimal(10,2) | Descuento aplicado | 0.00 |
| `importe_total` | decimal(10,2) | Importe final a pagar | 0.00 |
| `recargo` | decimal(10,2) | Recargo por mora | 0.00 |
| `saldo_pagado` | decimal(10,2) | Monto pagado del saldo | 0.00 |
| `recargo_pagado` | decimal(10,2) | Monto pagado del recargo | 0.00 |
| `saldo_actual` | decimal(10,2) | Saldo pendiente | 0.00 |
| `estado` | enum | Estado del arancel | 'pendiente' |
| `fecha_exonerado` | date | Fecha de exoneración | null |
| `observacion_exonerado` | text | Observación de exoneración | null |
| `fecha_recargo_anulado` | date | Fecha de anulación de recargo | null |
| `recargo_anulado_por` | bigint | Usuario que anuló el recargo | null |
| `observacion_recargo` | text | Observación de anulación de recargo | null |
| `created_by` | bigint | Usuario que creó el registro | null |
| `updated_by` | bigint | Usuario que actualizó el registro | null |
| `deleted_by` | bigint | Usuario que eliminó el registro | null |
| `created_at` | timestamp | Fecha de creación | CURRENT_TIMESTAMP |
| `updated_at` | timestamp | Fecha de actualización | CURRENT_TIMESTAMP |
| `deleted_at` | timestamp | Fecha de eliminación lógica | null |

### Estados Disponibles
- `pendiente`: Arancel pendiente de pago
- `pagado`: Arancel completamente pagado
- `exonerado`: Arancel exonerado por la institución

## Base URL
```
http://localhost:8000/api/v1/users-aranceles
```

## Autenticación
Todos los endpoints requieren autenticación mediante **Bearer Token** (Sanctum).

```http
Authorization: Bearer {token}
```

## Endpoints Disponibles

### 1. Listar Aranceles (Paginado)

**GET** `/api/v1/users-aranceles`

Obtiene una lista paginada de todos los aranceles de usuarios.

**Permisos requeridos:** `check.permissions:users_aranceles.index`

**Parámetros de consulta:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15)
- `user_id` (opcional): Filtrar por ID de usuario
- `estado` (opcional): Filtrar por estado (pendiente, pagado, exonerado)
- `rubro_id` (opcional): Filtrar por ID de rubro

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "rubro_id": 1,
                "user_id": 123,
                "aranceles_id": 5,
                "producto_id": null,
                "importe": "500.00",
                "beca": "50.00",
                "descuento": "25.00",
                "importe_total": "425.00",
                "recargo": "0.00",
                "saldo_pagado": "0.00",
                "recargo_pagado": "0.00",
                "saldo_actual": "425.00",
                "estado": "pendiente",
                "fecha_exonerado": null,
                "observacion_exonerado": null,
                "fecha_recargo_anulado": null,
                "recargo_anulado_por": null,
                "observacion_recargo": null,
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "rubro": {
                    "id": 1,
                    "nombre": "Colegiatura Enero"
                },
                "usuario": {
                    "id": 123,
                    "name": "Juan Pérez",
                    "email": "juan.perez@email.com"
                }
            }
        ],
        "per_page": 15,
        "total": 1
    },
    "message": "Aranceles obtenidos exitosamente"
}
```

### 2. Obtener Todos los Aranceles (Sin Paginación)

**GET** `/api/v1/users-aranceles/getall`

Obtiene todos los aranceles sin paginación.

**Permisos requeridos:** `check.permissions:users_aranceles.getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "rubro_id": 1,
            "user_id": 123,
            // ... resto de campos
        }
    ],
    "message": "Todos los aranceles obtenidos exitosamente"
}
```

### 3. Crear Nuevo Arancel

**POST** `/api/v1/users-aranceles`

Crea un nuevo registro de arancel para un usuario.

**Permisos requeridos:** `check.permissions:users_aranceles.store`

**Cuerpo de la petición:**
```json
{
    "rubro_id": 1,
    "user_id": 123,
    "aranceles_id": 5,
    "producto_id": null,
    "importe": 500.00,
    "beca": 50.00,
    "descuento": 25.00,
    "importe_total": 425.00,
    "recargo": 0.00,
    "saldo_actual": 425.00
}
```

**Validaciones:**
- `rubro_id`: opcional, debe existir en config_plan_pago_detalle
- `user_id`: requerido, debe existir en users
- `aranceles_id`: opcional, debe existir en config_aranceles
- `producto_id`: opcional, debe existir en inventario_producto
- `importe`: requerido, numérico, mínimo 0
- `beca`: opcional, numérico, mínimo 0
- `descuento`: opcional, numérico, mínimo 0
- `importe_total`: requerido, numérico, mínimo 0
- `recargo`: opcional, numérico, mínimo 0
- `saldo_actual`: requerido, numérico, mínimo 0

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "rubro_id": 1,
        "user_id": 123,
        // ... resto de campos
    },
    "message": "Arancel creado exitosamente"
}
```

### 4. Obtener Arancel por ID

**GET** `/api/v1/users-aranceles/{id}`

Obtiene un arancel específico por su ID.

**Permisos requeridos:** `check.permissions:users_aranceles.show`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "rubro_id": 1,
        "user_id": 123,
        // ... resto de campos con relaciones
    },
    "message": "Arancel obtenido exitosamente"
}
```

### 5. Actualizar Arancel

**PUT** `/api/v1/users-aranceles/{id}`

Actualiza un arancel existente.

**Permisos requeridos:** `check.permissions:users_aranceles.update`

**Cuerpo de la petición:** (Mismos campos que crear, todos opcionales)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        // ... campos actualizados
    },
    "message": "Arancel actualizado exitosamente"
}
```

### 6. Eliminar Arancel

**DELETE** `/api/v1/users-aranceles/{id}`

Elimina un arancel (eliminación lógica).

**Permisos requeridos:** `check.permissions:users_aranceles.destroy`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": null,
    "message": "Arancel eliminado exitosamente"
}
```

### 7. Quitar Recargos

**PATCH** `/api/v1/users-aranceles/quitar-recargos`

Anula los recargos de uno o varios aranceles.

**Permisos requeridos:** `check.permissions:users_aranceles.quitar_recargos`

**Cuerpo de la petición:**
```json
{
    "aranceles_ids": [1, 2, 3],
    "observacion_recargo": "Recargo anulado por política institucional"
}
```

**Validaciones:**
- `aranceles_ids`: requerido, array de IDs existentes
- `observacion_recargo`: requerido, string, máximo 500 caracteres

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "aranceles_actualizados": 3,
        "total_recargo_anulado": "150.00"
    },
    "message": "Recargos quitados exitosamente"
}
```

### 8. Exonerar Aranceles

**PATCH** `/api/v1/users-aranceles/exonerar`

Exonera uno o varios aranceles.

**Permisos requeridos:** `check.permissions:users_aranceles.exonerar`

**Cuerpo de la petición:**
```json
{
    "aranceles_ids": [1, 2, 3],
    "observacion_exonerado": "Exonerado por beca de excelencia académica"
}
```

**Validaciones:**
- `aranceles_ids`: requerido, array de IDs existentes
- `observacion_exonerado`: requerido, string, máximo 500 caracteres

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "aranceles_exonerados": 3,
        "total_exonerado": "1275.00"
    },
    "message": "Aranceles exonerados exitosamente"
}
```

### 9. Aplicar Plan de Pago

**POST** `/api/v1/users-aranceles/aplicar-plan-pago`

Aplica un plan de pago específico a un usuario, creando los aranceles correspondientes.

**Permisos requeridos:** `check.permissions:users_aranceles.aplicar_plan_pago`

**Cuerpo de la petición:**
```json
{
    "user_id": 123,
    "plan_pago_id": 5
}
```

**Validaciones:**
- `user_id`: requerido, debe existir en users
- `plan_pago_id`: requerido, debe existir en config_plan_pago

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "aranceles_creados": 12,
        "total_importe": "6000.00",
        "plan_aplicado": {
            "id": 5,
            "nombre": "Plan de Pago 2024 - Primaria"
        },
        "aranceles": [
            {
                "id": 15,
                "rubro_id": 1,
                "importe": "500.00",
                "importe_total": "500.00",
                "saldo_actual": "500.00"
            }
            // ... más aranceles creados
        ]
    },
    "message": "Plan de pago aplicado exitosamente"
}
```

### 10. Aplicar Pago

**PATCH** `/api/v1/users-aranceles/aplicar-pago`

Procesa el pago de uno o varios aranceles.

**Permisos requeridos:** `check.permissions:users_aranceles.aplicar_pago`

**Cuerpo de la petición:**
```json
{
    "aranceles_ids": [1, 2, 3]
}
```

**Validaciones:**
- `aranceles_ids`: requerido, array de IDs existentes con saldo pendiente

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "aranceles_pagados": 3,
        "total_pagado": "1275.00",
        "total_recargo_pagado": "75.00",
        "detalle_pagos": [
            {
                "id": 1,
                "saldo_pagado": "425.00",
                "recargo_pagado": "25.00",
                "total_pago": "450.00"
            }
            // ... más detalles
        ]
    },
    "message": "Pagos aplicados exitosamente"
}
```

## Endpoints de Consulta Específica

### 11. Aranceles por Usuario

**GET** `/api/v1/users-aranceles/usuario/{userId}`

Obtiene todos los aranceles de un usuario específico.

**Permisos requeridos:** `check.permissions:users_aranceles.by_user`

### 12. Aranceles Pendientes por Usuario

**GET** `/api/v1/users-aranceles/usuario/{userId}/pendientes`

Obtiene los aranceles pendientes de un usuario específico.

**Permisos requeridos:** `check.permissions:users_aranceles.pendientes_by_user`

### 13. Aranceles con Recargo

**GET** `/api/v1/users-aranceles/reportes/con-recargo`

Obtiene todos los aranceles que tienen recargo aplicado.

**Permisos requeridos:** `check.permissions:users_aranceles.con_recargo`

### 14. Aranceles con Saldo Pendiente

**GET** `/api/v1/users-aranceles/reportes/con-saldo-pendiente`

Obtiene todos los aranceles con saldo pendiente de pago.

**Permisos requeridos:** `check.permissions:users_aranceles.con_saldo_pendiente`

### 15. Estadísticas de Aranceles

**GET** `/api/v1/users-aranceles/reportes/estadisticas`

Obtiene estadísticas generales de los aranceles.

**Permisos requeridos:** `check.permissions:users_aranceles.estadisticas`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "total_aranceles": 1250,
        "total_pendientes": 450,
        "total_pagados": 650,
        "total_exonerados": 150,
        "total_importe_pendiente": "225000.00",
        "total_recargos_pendientes": "15000.00",
        "porcentaje_cobranza": 75.5
    },
    "message": "Estadísticas obtenidas exitosamente"
}
```

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 201 | Created - Recurso creado exitosamente |
| 400 | Bad Request - Error en los datos enviados |
| 401 | Unauthorized - Token de autenticación inválido |
| 403 | Forbidden - Sin permisos para realizar la acción |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Errores de validación |
| 500 | Internal Server Error - Error interno del servidor |

## Estructura de Errores

### Error de Validación (422)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "user_id": ["El campo user_id es obligatorio"],
        "importe": ["El importe debe ser mayor a 0"]
    }
}
```

### Error de Permisos (403)
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción",
    "errors": []
}
```

### Error de Recurso No Encontrado (404)
```json
{
    "success": false,
    "message": "Arancel no encontrado",
    "errors": []
}
```

## Ejemplos de Uso con cURL

### Crear un nuevo arancel
```bash
curl -X POST "http://localhost:8000/api/v1/users-aranceles" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "user_id": 123,
    "aranceles_id": 5,
    "importe": 500.00,
    "importe_total": 425.00,
    "saldo_actual": 425.00
  }'
```

### Aplicar plan de pago
```bash
curl -X POST "http://localhost:8000/api/v1/users-aranceles/aplicar-plan-pago" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "user_id": 123,
    "plan_pago_id": 5
  }'
```

### Procesar pago
```bash
curl -X PATCH "http://localhost:8000/api/v1/users-aranceles/aplicar-pago" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "aranceles_ids": [1, 2, 3]
  }'
```

## Auditoría

El sistema incluye auditoría automática mediante el trait `Auditable`. Se registran automáticamente:

- **Eventos auditados:** `updated` (solo actualizaciones)
- **Campos excluidos:** `updated_at`, `created_at`, `deleted_at`
- **Información registrada:** Usuario que realizó el cambio, valores anteriores y nuevos, timestamp

### Consultar auditoría de un arancel
```php
$arancel = UsersAranceles::find(1);
$auditorias = $arancel->audits; // Obtener todas las auditorías
$cambiosRecientes = $arancel->getRecentChanges(10); // Últimos 10 cambios
```

## Notas Importantes

1. **Eliminación Lógica:** Los aranceles utilizan soft delete, por lo que no se eliminan físicamente de la base de datos.

2. **Validaciones de Negocio:** 
   - No se puede aplicar un plan de pago si ya existen aranceles para los mismos rubros del usuario
   - Solo se pueden procesar pagos de aranceles con estado "pendiente"
   - Los recargos solo se pueden anular si el arancel tiene recargo > 0

3. **Cálculos Automáticos:**
   - `importe_total` = `importe` - `beca` - `descuento`
   - `saldo_actual` = `importe_total` + `recargo` - `saldo_pagado` - `recargo_pagado`

4. **Relaciones:**
   - Un arancel puede estar relacionado con un rubro (plan de pago), un arancel de configuración, o un producto del inventario
   - Cada arancel pertenece a un usuario (alumno)
   - Se mantiene el historial de quién creó, actualizó o eliminó cada registro

5. **Estados:**
   - `pendiente`: Estado inicial, permite modificaciones
   - `pagado`: No permite modificaciones de montos
   - `exonerado`: No permite modificaciones, saldo se considera como pagado

---

## Nuevos Endpoints Agregados

### 16. Obtener Períodos Lectivos

**Endpoint:** `GET /api/v1/users-aranceles/periodos-lectivos`

**Descripción:** Obtiene todos los períodos lectivos disponibles para la gestión de aranceles.

**Permisos Requeridos:** `check.permissions:users_aranceles.periodos_lectivos`

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "2024-I",
            "fecha_inicio": "2024-01-15",
            "fecha_fin": "2024-06-30",
            "activo": true
        },
        {
            "id": 2,
            "nombre": "2024-II",
            "fecha_inicio": "2024-07-01",
            "fecha_fin": "2024-12-15",
            "activo": true
        }
    ],
    "message": "Períodos lectivos obtenidos correctamente"
}
```

**Ejemplo de petición cURL:**
```bash
curl -X GET "http://localhost:8000/api/v1/users-aranceles/periodos-lectivos" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 17. Obtener Planes de Pago por Período Lectivo

**Endpoint:** `GET /api/v1/users-aranceles/planes-pago/periodo/{periodoLectivoId}`

**Descripción:** Obtiene todos los planes de pago activos para un período lectivo específico.

**Permisos Requeridos:** `check.permissions:users_aranceles.planes_pago_por_periodo`

**Parámetros de URL:**
- `periodoLectivoId` (integer, requerido): ID del período lectivo

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Plan Mensual 2024-I",
            "descripcion": "Plan de pago mensual para el período 2024-I",
            "activo": true,
            "periodo_lectivo": {
                "id": 1,
                "nombre": "2024-I"
            },
            "detalles": [
                {
                    "id": 1,
                    "numero_cuota": 1,
                    "fecha_vencimiento": "2024-02-15",
                    "porcentaje": 20.00,
                    "rubro": {
                        "id": 1,
                        "nombre": "Matrícula"
                    }
                }
            ]
        }
    ],
    "message": "Planes de pago obtenidos correctamente"
}
```

**Respuesta de Error (404):**
```json
{
    "success": false,
    "message": "No se encontraron planes de pago para el período lectivo especificado"
}
```

**Ejemplo de petición cURL:**
```bash
curl -X GET "http://localhost:8000/api/v1/users-aranceles/planes-pago/periodo/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

---

## Tabla de Permisos Actualizada

| Permiso | Descripción |
|---------|-------------|
| `check.permissions:users_aranceles.index` | Ver listado paginado de aranceles |
| `check.permissions:users_aranceles.getall` | Ver todos los aranceles |
| `check.permissions:users_aranceles.store` | Crear nuevo arancel |
| `check.permissions:users_aranceles.show` | Ver detalle de un arancel |
| `check.permissions:users_aranceles.update` | Actualizar arancel |
| `check.permissions:users_aranceles.destroy` | Eliminar arancel |
| `check.permissions:users_aranceles.quitar_recargos` | Quitar recargos de aranceles |
| `check.permissions:users_aranceles.exonerar` | Exonerar aranceles |
| `check.permissions:users_aranceles.aplicar_plan_pago` | Aplicar plan de pago |
| `check.permissions:users_aranceles.aplicar_pago` | Aplicar pago a arancel |
| `check.permissions:users_aranceles.by_user` | Ver aranceles por usuario |
| `check.permissions:users_aranceles.pendientes_by_user` | Ver aranceles pendientes por usuario |
| `check.permissions:users_aranceles.con_recargo` | Ver aranceles con recargo |
| `check.permissions:users_aranceles.con_saldo_pendiente` | Ver aranceles con saldo pendiente |
| `check.permissions:users_aranceles.estadisticas` | Ver estadísticas de aranceles |
| `check.permissions:users_aranceles.periodos_lectivos` | **NUEVO:** Ver períodos lectivos |
| `check.permissions:users_aranceles.planes_pago_por_periodo` | **NUEVO:** Ver planes de pago por período |