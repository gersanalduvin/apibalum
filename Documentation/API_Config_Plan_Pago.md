# API Configuración de Planes de Pago

## Descripción General
Esta API permite gestionar los planes de pago y sus detalles asociados en el sistema. Los planes de pago definen la estructura de pagos para diferentes períodos lectivos, mientras que los detalles especifican los conceptos individuales de pago.

## Estructura de Datos

### ConfigPlanPago (Tabla: config_plan_pago)
**Campos de la Base de Datos:**
- **id**: `bigint unsigned` - ID autoincremental (Primary Key)
- **uuid**: `char(36)` - Identificador único universal (Unique)
- **nombre**: `varchar(255)` - Nombre del plan de pago (Unique)
- **estado**: `tinyint(1)` - Estado del plan (1=activo, 0=inactivo, default: 1)
- **periodo_lectivo_id**: `bigint unsigned` - ID del período lectivo asociado (Foreign Key)
- **created_by**: `bigint unsigned` - ID del usuario que creó el registro (Foreign Key, nullable)
- **updated_by**: `bigint unsigned` - ID del usuario que actualizó el registro (Foreign Key, nullable)
- **deleted_by**: `bigint unsigned` - ID del usuario que eliminó el registro (Foreign Key, nullable)
- **cambios**: `json` - Historial de cambios (nullable)
- **is_synced**: `tinyint(1)` - Indica si está sincronizado (default: 0)
- **synced_at**: `timestamp` - Fecha de sincronización (nullable)
- **updated_locally_at**: `timestamp` - Fecha de actualización local (nullable)
- **version**: `int` - Versión del registro (default: 1)
- **created_at**: `timestamp` - Fecha de creación (nullable)
- **updated_at**: `timestamp` - Fecha de actualización (nullable)
- **deleted_at**: `timestamp` - Fecha de eliminación lógica (nullable)

**Relaciones del Modelo:**
- `periodoLectivo()`: Pertenece a un PeriodoLectivo
- `detalles()`: Tiene muchos ConfigPlanPagoDetalle
- `createdBy()`: Pertenece a un User (quien creó)
- `updatedBy()`: Pertenece a un User (quien actualizó)
- `deletedBy()`: Pertenece a un User (quien eliminó)

**Scopes Disponibles:**
- `active()`: Solo registros activos
- `inactive()`: Solo registros inactivos
- `byPeriodoLectivo($periodoLectivoId)`: Por período lectivo

**Accessors:**
- `estado_text`: Retorna "Activo" o "Inactivo"
- `total_detalles`: Cuenta total de detalles
- `total_importe`: Suma total de importes de detalles

### ConfigPlanPagoDetalle (Tabla: config_plan_pago_detalle)
**Campos de la Base de Datos:**
- **id**: `bigint unsigned` - ID autoincremental (Primary Key)
- **uuid**: `char(36)` - Identificador único universal (Unique)
- **plan_pago_id**: `bigint unsigned` - ID del plan de pago padre (Foreign Key)
- **codigo**: `varchar(50)` - Código único del concepto
- **nombre**: `varchar(255)` - Nombre del concepto de pago
- **importe**: `decimal(10,2)` - Monto del concepto (default: 0.00)
- **cuenta_debito_id**: `bigint unsigned` - ID de la cuenta de débito (Foreign Key)
- **cuenta_credito_id**: `bigint unsigned` - ID de la cuenta de crédito (Foreign Key)
- **cuenta_recargo_id**: `bigint unsigned` - ID de la cuenta de recargo (Foreign Key, nullable)
- **es_colegiatura**: `tinyint(1)` - Indica si es colegiatura (default: 0)
- **asociar_mes**: `enum` - Mes asociado (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre, nullable)
- **fecha_vencimiento**: `date` - Fecha de vencimiento (nullable)
- **importe_recargo**: `decimal(10,2)` - Monto del recargo (default: 0.00, nullable)
- **tipo_recargo**: `enum` - Tipo de recargo (fijo, porcentaje, nullable)
- **moneda**: `tinyint(1)` - Moneda del concepto (0=Córdoba, 1=Dólar, default: 0)
- **created_by**: `bigint unsigned` - ID del usuario que creó el registro (Foreign Key, nullable)
- **updated_by**: `bigint unsigned` - ID del usuario que actualizó el registro (Foreign Key, nullable)
- **deleted_by**: `bigint unsigned` - ID del usuario que eliminó el registro (Foreign Key, nullable)
- **cambios**: `json` - Historial de cambios (nullable)
- **is_synced**: `tinyint(1)` - Indica si está sincronizado (default: 0)
- **synced_at**: `timestamp` - Fecha de sincronización (nullable)
- **updated_locally_at**: `timestamp` - Fecha de actualización local (nullable)
- **version**: `int` - Versión del registro (default: 1)
- **created_at**: `timestamp` - Fecha de creación (nullable)
- **updated_at**: `timestamp` - Fecha de actualización (nullable)
- **deleted_at**: `timestamp` - Fecha de eliminación lógica (nullable)

**Índices de la Base de Datos:**
- Índice único compuesto: `plan_pago_id + codigo`
- Índice único compuesto: `plan_pago_id + nombre`
- Índice: `plan_pago_id`
- Índice: `cuenta_debito_id`
- Índice: `cuenta_credito_id`
- Índice: `cuenta_recargo_id`
- Índice: `es_colegiatura`
- Índice: `asociar_mes`
- Índice: `moneda`

**Relaciones del Modelo:**
- `planPago()`: Pertenece a un ConfigPlanPago
- `cuentaDebito()`: Pertenece a un CatalogoCuenta (cuenta de débito)
- `cuentaCredito()`: Pertenece a un CatalogoCuenta (cuenta de crédito)
- `cuentaRecargo()`: Pertenece a un CatalogoCuenta (cuenta de recargo)
- `createdBy()`: Pertenece a un User (quien creó)
- `updatedBy()`: Pertenece a un User (quien actualizó)
- `deletedBy()`: Pertenece a un User (quien eliminó)

**Scopes Disponibles:**
- `byColegiatura($esColegiatura)`: Por tipo de colegiatura
- `byMes($mes)`: Por mes específico
- `byMoneda($moneda)`: Por tipo de moneda
- `byPlanPago($planPagoId)`: Por plan de pago

**Accessors:**
- `moneda_text`: Retorna "Córdoba" o "Dólar"
- `es_colegiatura_text`: Retorna "Sí" o "No"
- `tipo_recargo_text`: Retorna "Fijo" o "Porcentaje"
- `importe_formateado`: Formato de moneda para importe
- `importe_recargo_formateado`: Formato de moneda para recargo
- `asociar_mes_text`: Capitaliza el nombre del mes

## Endpoints - Planes de Pago

### 1. Listar Planes de Pago (Paginado)
**GET** `/api/v1/config-plan-pago`

**Permisos requeridos:** `config_plan_pago.index`

**Parámetros de consulta:**
- `search` (opcional): Búsqueda por nombre
- `estado` (opcional): Filtrar por estado (true/false)
- `periodo_lectivo_id` (opcional): Filtrar por período lectivo
- `per_page` (opcional): Elementos por página (default: 15)
- `page` (opcional): Número de página

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
                "nombre": "Plan de Pago 2024",
                "estado": true,
                "estado_text": "Activo",
                "periodo_lectivo_id": 1,
                "total_detalles": 5,
                "total_importe": "1500.00",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "per_page": 15,
        "total": 1
    },
    "message": "Planes de pago obtenidos exitosamente"
}
```

### 2. Obtener Todos los Planes Activos
**GET** `/api/v1/config-plan-pago/getall/all`

**Permisos requeridos:** `config_plan_pago.getall`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Plan de Pago 2024",
            "estado": true,
            "periodo_lectivo_id": 1
        }
    ],
    "message": "Planes de pago activos obtenidos exitosamente"
}
```

### 3. Obtener Planes Inactivos
**GET** `/api/v1/config-plan-pago/inactive/all`

**Permisos requeridos:** `config_plan_pago.inactive`

### 4. Buscar Planes de Pago
**GET** `/api/v1/config-plan-pago/search/query`

**Permisos requeridos:** `config_plan_pago.search`

**Parámetros de consulta:**
- `q` (requerido): Término de búsqueda

### 5. Obtener Planes por Período Lectivo
**GET** `/api/v1/config-plan-pago/periodo-lectivo/{periodoLectivoId}`

**Permisos requeridos:** `config_plan_pago.by_periodo`

### 6. Crear Plan de Pago
**POST** `/api/v1/config-plan-pago`

**Permisos requeridos:** `config_plan_pago.store`

**Cuerpo de la petición:**
```json
{
    "nombre": "Plan de Pago 2024",
    "estado": true,
    "periodo_lectivo_id": 1
}
```

**Validaciones:**
- `nombre`: requerido, string, máximo 255 caracteres, único
- `estado`: opcional, boolean (default: true)
- `periodo_lectivo_id`: requerido, entero, debe existir en la tabla

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "nombre": "Plan de Pago 2024",
        "estado": true,
        "periodo_lectivo_id": 1,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    "message": "Plan de pago creado exitosamente"
}
```

### 7. Mostrar Plan de Pago
**GET** `/api/v1/config-plan-pago/{id}`

**Permisos requeridos:** `config_plan_pago.show`

### 8. Actualizar Plan de Pago
**PUT/PATCH** `/api/v1/config-plan-pago/{id}`

**Permisos requeridos:** `config_plan_pago.update`

### 9. Eliminar Plan de Pago
**DELETE** `/api/v1/config-plan-pago/{id}`

**Permisos requeridos:** `config_plan_pago.destroy`

### 10. Cambiar Estado del Plan
**PATCH** `/api/v1/config-plan-pago/{id}/toggle-status`

**Permisos requeridos:** `config_plan_pago.toggle_status`

### 11. Activar Plan
**PATCH** `/api/v1/config-plan-pago/{id}/activate`

**Permisos requeridos:** `config_plan_pago.activate`

### 12. Desactivar Plan
**PATCH** `/api/v1/config-plan-pago/{id}/deactivate`

**Permisos requeridos:** `config_plan_pago.deactivate`

### 13. Obtener Períodos Lectivos
**GET** `/api/v1/config-plan-pago/periodos-lectivos/all`

**Permisos requeridos:** `config_plan_pago.index`

**Descripción:** Obtiene todos los períodos lectivos disponibles para usar en selects o formularios.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "nombre": "Período 2024-2025",
            "periodo_nota": true,
            "periodo_matricula": false
        },
        {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "nombre": "Período 2025-2026",
            "periodo_nota": false,
            "periodo_matricula": true
        }
    ],
    "message": "Períodos lectivos obtenidos exitosamente"
}
```

### 14. Obtener Catálogo de Cuentas
**GET** `/api/v1/config-plan-pago/catalogo-cuentas/all`

**Permisos requeridos:** `config_plan_pago.index`

**Descripción:** Obtiene todas las cuentas del catálogo contable disponibles para usar en selects o formularios de planes de pago.

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "1001",
            "nombre": "Caja General",
            "codigo_completo": "1001",
            "tipo": "activo",
            "nivel": 1,
            "es_grupo": false,
            "permite_movimiento": true,
            "naturaleza": "deudora",
            "moneda_usd": false,
            "padre_id": null,
            "estado": "activo"
        },
        {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "codigo": "4001",
            "nombre": "Ingresos por Colegiaturas",
            "codigo_completo": "4001",
            "tipo": "ingreso",
            "nivel": 1,
            "es_grupo": false,
            "permite_movimiento": true,
            "naturaleza": "acreedora",
            "moneda_usd": false,
            "padre_id": null,
            "estado": "activo"
        }
    ],
    "message": "Catálogo de cuentas obtenido exitosamente"
}
```

## Endpoints - Detalles de Planes de Pago

### 1. Listar Detalles (Paginado)
**GET** `/api/v1/config-plan-pago-detalle`

**Permisos requeridos:** `config_plan_pago_detalle.index`

**Parámetros de consulta:**
- `search` (opcional): Búsqueda por código o nombre
- `plan_pago_id` (opcional): Filtrar por plan de pago
- `es_colegiatura` (opcional): Filtrar por colegiaturas (true/false)
- `mes` (opcional): Filtrar por mes (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre)
- `moneda` (opcional): Filtrar por moneda (false=Córdoba, true=Dólar)
- `per_page` (opcional): Elementos por página (default: 15)

### 2. Obtener Todos los Detalles
**GET** `/api/v1/config-plan-pago-detalle/getall/all`

**Permisos requeridos:** `config_plan_pago_detalle.getall`

### 3. Buscar Detalles
**GET** `/api/v1/config-plan-pago-detalle/search/query`

**Permisos requeridos:** `config_plan_pago_detalle.search`

### 4. Obtener Plan de Pago con Detalles
**GET** `/api/v1/config-plan-pago-detalle/plan-pago/{planPagoId}`

**Permisos requeridos:** `config_plan_pago_detalle.by_plan`

**Descripción:** Obtiene la información completa del plan de pago junto con sus detalles paginados.

**Parámetros de consulta:**
- `per_page` (opcional): Número de detalles por página (default: 15)

**Respuesta exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "plan_pago": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "codigo": "PP2024-001",
            "nombre": "Plan de Pago 2024 - Primer Semestre",
            "descripcion": "Plan de pagos para el primer semestre del año 2024",
            "periodo_lectivo_id": 1,
            "activo": true,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "periodo_lectivo": {
                "id": 1,
                "nombre": "2024 - Primer Semestre",
                "fecha_inicio": "2024-01-15",
                "fecha_fin": "2024-06-30"
            }
        },
        "detalles": {
            "current_page": 1,
            "data": [
                {
                    "id": 1,
                    "uuid": "550e8400-e29b-41d4-a716-446655440001",
                    "plan_pago_id": 1,
                    "codigo": "COL001",
                    "nombre": "Colegiatura Enero",
                    "importe": 500.00,
                    "cuenta_debito_id": 1,
                    "cuenta_credito_id": 2,
                    "cuenta_recargo_id": 3,
                    "es_colegiatura": true,
                    "asociar_mes": "enero",
                    "fecha_vencimiento": "2024-01-31",
                    "importe_recargo": 50.00,
                    "tipo_recargo": "fijo",
                    "moneda": false,
                    "created_at": "2024-01-15T10:30:00.000000Z",
                    "updated_at": "2024-01-15T10:30:00.000000Z",
                    "plan_pago": {
                        "id": 1,
                        "nombre": "Plan de Pago 2024 - Primer Semestre"
                    },
                    "cuenta_debito": {
                        "id": 1,
                        "codigo": "1101",
                        "nombre": "Caja General"
                    },
                    "cuenta_credito": {
                        "id": 2,
                        "codigo": "4101",
                        "nombre": "Ingresos por Colegiaturas"
                    },
                    "cuenta_recargo": {
                        "id": 3,
                        "codigo": "4102",
                        "nombre": "Ingresos por Recargos"
                    }
                }
            ],
            "first_page_url": "http://localhost:8081/api/v1/config-plan-pago-detalle/plan-pago/1?page=1",
            "from": 1,
            "last_page": 1,
            "last_page_url": "http://localhost:8081/api/v1/config-plan-pago-detalle/plan-pago/1?page=1",
            "links": [
                {
                    "url": null,
                    "label": "&laquo; Previous",
                    "active": false
                },
                {
                    "url": "http://localhost:8081/api/v1/config-plan-pago-detalle/plan-pago/1?page=1",
                    "label": "1",
                    "active": true
                },
                {
                    "url": null,
                    "label": "Next &raquo;",
                    "active": false
                }
            ],
            "next_page_url": null,
            "path": "http://localhost:8081/api/v1/config-plan-pago-detalle/plan-pago/1",
            "per_page": 15,
            "prev_page_url": null,
            "to": 1,
            "total": 1
        }
    },
    "message": "Plan de pago y detalles obtenidos exitosamente"
}
```

**Respuesta de error (400 Bad Request):**
```json
{
    "success": false,
    "message": "Plan de pago no encontrado"
}
```

### 5. Obtener Solo Colegiaturas
**GET** `/api/v1/config-plan-pago-detalle/colegiaturas/all`

**Permisos requeridos:** `config_plan_pago_detalle.colegiaturas`

### 6. Obtener Detalles por Mes
**GET** `/api/v1/config-plan-pago-detalle/mes/{mes}`

**Permisos requeridos:** `config_plan_pago_detalle.by_mes`

### 7. Obtener Detalles por Moneda
**GET** `/api/v1/config-plan-pago-detalle/moneda/{moneda}`

**Permisos requeridos:** `config_plan_pago_detalle.by_moneda`

### 8. Crear Detalle
**POST** `/api/v1/config-plan-pago-detalle`

**Permisos requeridos:** `config_plan_pago_detalle.store`

**Cuerpo de la petición:**
```json
{
    "codigo": "COL001",
    "nombre": "Colegiatura Enero",
    "importe": 500.00,
    "moneda": false,
    "plan_pago_id": 1,
    "cuenta_debito_id": 1,
    "cuenta_credito_id": 2,
    "cuenta_recargo_id": 3,
    "es_colegiatura": true,
    "asociar_mes": "enero",
    "fecha_vencimiento": "2024-01-31",
    "importe_recargo": 50.00,
    "tipo_recargo": "fijo"
}
```

**Validaciones:**
- `codigo`: requerido, string, máximo 50 caracteres, único dentro del plan
- `nombre`: requerido, string, máximo 255 caracteres, único dentro del plan
- `importe`: requerido, numérico (decimal 10,2), mayor que 0
- `moneda`: requerido, boolean (false=Córdoba, true=Dólar)
- `plan_pago_id`: opcional, entero, debe existir
- `cuenta_debito_id`: opcional, entero, debe existir
- `cuenta_credito_id`: opcional, entero, debe existir, diferente a cuenta_debito_id
- `cuenta_recargo_id`: opcional, entero, debe existir si se proporciona
- `es_colegiatura`: opcional, boolean (default: false)
- `asociar_mes`: opcional, enum (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre)
- `fecha_vencimiento`: opcional, fecha (date)
- `importe_recargo`: opcional, numérico (decimal 10,2), mínimo 0
- `tipo_recargo`: requerido si hay importe_recargo, enum (fijo, porcentaje)

### 9. Mostrar Detalle
**GET** `/api/v1/config-plan-pago-detalle/{id}`

**Permisos requeridos:** `config_plan_pago_detalle.show`

### 10. Actualizar Detalle
**PUT/PATCH** `/api/v1/config-plan-pago-detalle/{id}`

**Permisos requeridos:** `config_plan_pago_detalle.update`

### 11. Eliminar Detalle
**DELETE** `/api/v1/config-plan-pago-detalle/{id}`

**Permisos requeridos:** `config_plan_pago_detalle.destroy`

### 12. Duplicar Detalle
**POST** `/api/v1/config-plan-pago-detalle/{id}/duplicate`

**Permisos requeridos:** `config_plan_pago_detalle.duplicate`

**Cuerpo de la petición:**
```json
{
    "nuevo_codigo": "COL002",
    "nuevo_nombre": "Colegiatura Febrero"
}
```

### 13. Verificar Código Único
**GET** `/api/v1/config-plan-pago-detalle/check/codigo`

**Permisos requeridos:** `config_plan_pago_detalle.check_codigo`

**Parámetros de consulta:**
- `codigo` (requerido): Código a verificar
- `plan_pago_id` (requerido): ID del plan de pago
- `exclude_id` (opcional): ID a excluir de la verificación

### 14. Verificar Nombre Único
**GET** `/api/v1/config-plan-pago-detalle/check/nombre`

**Permisos requeridos:** `config_plan_pago_detalle.check_nombre`

**Parámetros de consulta:**
- `nombre` (requerido): Nombre a verificar
- `plan_pago_id` (requerido): ID del plan de pago
- `exclude_id` (opcional): ID a excluir de la verificación

## Códigos de Respuesta

- **200 OK**: Operación exitosa
- **201 Created**: Recurso creado exitosamente
- **400 Bad Request**: Error en la petición
- **401 Unauthorized**: No autorizado
- **403 Forbidden**: Sin permisos suficientes
- **404 Not Found**: Recurso no encontrado
- **422 Unprocessable Entity**: Errores de validación
- **500 Internal Server Error**: Error interno del servidor

## Ejemplos de Respuestas de Error

### Error de Validación (422)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "nombre": ["El campo nombre es obligatorio"],
        "importe": ["El campo importe debe ser un número"]
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

### Recurso No Encontrado (404)
```json
{
    "success": false,
    "message": "Plan de pago no encontrado"
}
```

## Notas Importantes

1. **Autenticación**: Todas las rutas requieren autenticación mediante token Bearer.
2. **Permisos**: Cada endpoint requiere permisos específicos definidos en el sistema.
3. **Soft Delete**: Los registros eliminados se marcan como eliminados lógicamente.
4. **Auditoría**: Todos los cambios se registran en el campo `cambios` con historial completo.
5. **UUID**: Cada registro tiene un UUID único para identificación externa.
6. **Validaciones**: Los códigos y nombres deben ser únicos dentro del mismo plan de pago.
7. **Monedas**: false=Córdoba (Moneda local), true=Dólar (Moneda extranjera).
8. **Recargos**: Si se especifica un recargo, debe indicarse el tipo (fijo o porcentaje).
9. **Meses**: Los meses se especifican como texto (enero, febrero, marzo, etc.).
10. **Sincronización**: Incluye campos para sincronización offline (`is_synced`, `synced_at`, etc.).