# API Config Parámetros

## Descripción
API para gestionar los parámetros de configuración del sistema. Permite obtener y actualizar/crear los parámetros de configuración globales.

## Endpoints

### 1. Obtener Parámetros de Configuración

**GET** `/api/v1/config-parametros`

Obtiene los parámetros de configuración actuales del sistema.

#### Permisos Requeridos
- `config_parametros.show`

#### Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

#### Respuesta Exitosa (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "consecutivo_recibo_oficial": 1000,
        "consecutivo_recibo_interno": 2000,
        "tasa_cambio_dolar": "7.5000",
        "terminal_separada": true,
        "cambios": [],
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "is_synced": true,
        "synced_at": "2024-01-15T10:30:00.000000Z",
        "updated_locally_at": null,
        "version": 1,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Parámetros obtenidos correctamente"
}
```

#### Respuesta cuando no hay parámetros (200 OK)
```json
{
    "success": true,
    "data": {
        "consecutivo_recibo_oficial": 1,
        "consecutivo_recibo_interno": 1,
        "tasa_cambio_dolar": "1.0000",
        "terminal_separada": false
    },
    "message": "Parámetros por defecto"
}
```

#### Respuesta de Error (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error al obtener los parámetros",
    "errors": {
        "error": "Mensaje de error específico"
    }
}
```

---

### 2. Actualizar o Crear Parámetros de Configuración

**PUT** `/api/v1/config-parametros`

Actualiza los parámetros existentes o crea un nuevo registro si no existe.

#### Permisos Requeridos
- `config_parametros.updateOrCreate`

#### Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

#### Parámetros del Body
```json
{
    "consecutivo_recibo_oficial": 1500,
    "consecutivo_recibo_interno": 2500,
    "tasa_cambio_dolar": "8.2500",
    "terminal_separada": false
}
```

#### Validaciones
- `consecutivo_recibo_oficial`: Requerido, entero, mínimo 1
- `consecutivo_recibo_interno`: Requerido, entero, mínimo 1
- `tasa_cambio_dolar`: Requerido, numérico, mínimo 0.0001, máximo 9999.9999
- `terminal_separada`: Requerido, booleano

#### Respuesta Exitosa - Actualización (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "consecutivo_recibo_oficial": 1500,
        "consecutivo_recibo_interno": 2500,
        "tasa_cambio_dolar": "8.2500",
        "terminal_separada": false,
        "cambios": [
            {
                "campo": "consecutivo_recibo_oficial",
                "valor_anterior": 1000,
                "valor_nuevo": 1500,
                "usuario": "admin@example.com",
                "fecha": "2024-01-15T14:30:00.000000Z"
            },
            {
                "campo": "tasa_cambio_dolar",
                "valor_anterior": "7.5000",
                "valor_nuevo": "8.2500",
                "usuario": "admin@example.com",
                "fecha": "2024-01-15T14:30:00.000000Z"
            }
        ],
        "created_by": 1,
        "updated_by": 1,
        "deleted_by": null,
        "is_synced": false,
        "synced_at": "2024-01-15T10:30:00.000000Z",
        "updated_locally_at": "2024-01-15T14:30:00.000000Z",
        "version": 2,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T14:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Parámetros actualizados correctamente"
}
```

#### Respuesta Exitosa - Creación (201 Created)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "consecutivo_recibo_oficial": 1500,
        "consecutivo_recibo_interno": 2500,
        "tasa_cambio_dolar": "8.2500",
        "terminal_separada": false,
        "cambios": [],
        "created_by": 1,
        "updated_by": null,
        "deleted_by": null,
        "is_synced": false,
        "synced_at": null,
        "updated_locally_at": "2024-01-15T14:30:00.000000Z",
        "version": 1,
        "created_at": "2024-01-15T14:30:00.000000Z",
        "updated_at": "2024-01-15T14:30:00.000000Z",
        "deleted_at": null
    },
    "message": "Parámetros creados correctamente"
}
```

#### Respuesta de Validación (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "consecutivo_recibo_oficial": [
            "El campo consecutivo recibo oficial es obligatorio."
        ],
        "tasa_cambio_dolar": [
            "El campo tasa cambio dolar debe ser un número.",
            "El campo tasa cambio dolar debe ser al menos 0.0001."
        ]
    }
}
```

#### Respuesta de Error (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error al procesar los parámetros",
    "errors": {
        "error": "Mensaje de error específico"
    }
}
```

---

## Estructura de Datos

### Campos Principales
- `consecutivo_recibo_oficial`: Número consecutivo para recibos oficiales
- `consecutivo_recibo_interno`: Número consecutivo para recibos internos
- `tasa_cambio_dolar`: Tasa de cambio del dólar (4 decimales)
- `terminal_separada`: Indica si se usa terminal separada

### Campos de Auditoría
- `cambios`: Array con historial de cambios del registro
- `created_by`: ID del usuario que creó el registro
- `updated_by`: ID del usuario que actualizó el registro
- `deleted_by`: ID del usuario que eliminó el registro

### Campos de Sincronización
- `uuid`: Identificador único universal
- `is_synced`: Indica si está sincronizado con el servidor
- `synced_at`: Fecha de última sincronización
- `updated_locally_at`: Fecha de última modificación local
- `version`: Versión del registro para control de conflictos

---

## Notas Importantes

1. **Registro Único**: Solo puede existir un registro de parámetros en el sistema
2. **Auditoría**: Todos los cambios se registran en el campo `cambios`
3. **Valores por Defecto**: Si no existe registro, se devuelven valores por defecto
4. **Permisos**: Cada endpoint requiere permisos específicos
5. **Sincronización**: Compatible con modo offline si está habilitado

---

## Ejemplos de Uso

### Obtener parámetros actuales
```bash
curl -X GET "http://localhost:8000/api/v1/config-parametros" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### Actualizar parámetros
```bash
curl -X PUT "http://localhost:8000/api/v1/config-parametros" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "consecutivo_recibo_oficial": 2000,
    "consecutivo_recibo_interno": 3000,
    "tasa_cambio_dolar": "9.5000",
    "terminal_separada": true
  }'
```