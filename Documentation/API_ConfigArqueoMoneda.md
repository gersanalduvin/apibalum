# API: Configuración de Arqueo Moneda

## Permisos
- Acciones protegidas por: `config_arqueo_moneda.index|show|store|update|destroy|getall`

## Objetivo
- Mantener el catálogo de denominaciones por moneda para arqueos de caja. `moneda=0` Córdoba, `moneda=1` Dólar.

## Endpoints
- `GET /api/v1/config-arqueo-moneda/` lista paginada (orden asc por `orden`)
- `GET /api/v1/config-arqueo-moneda/getall` lista completa
- `POST /api/v1/config-arqueo-moneda/` crea denominación
- `GET /api/v1/config-arqueo-moneda/{id}` detalle por id
- `PUT /api/v1/config-arqueo-moneda/{id}` actualiza denominación
- `DELETE /api/v1/config-arqueo-moneda/{id}` elimina (soft delete)

## Cuerpo (POST/PUT)
- `moneda`: boolean (0/1)
- `denominacion`: string(100)
- `multiplicador`: decimal(2)
- `orden`: int

## Respuestas
- Éxito: `{ success: true, data: {...}, message: '...' }`
- Error validación: `422` con `errors`
- No encontrado: `404`
