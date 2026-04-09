# API: Configuración de Arqueo Detalle

## Permiso requerido
- Todas las rutas protegidas por: `arqueo_caja`

## Objetivo
- Registrar las partidas del arqueo por denominación: cantidad y total por cada `moneda_id` asociada al `arqueo_id`.

## Endpoints
- `GET /api/v1/config-arqueo-detalle/` lista paginada
- `GET /api/v1/config-arqueo-detalle/getall` lista completa
- `POST /api/v1/config-arqueo-detalle/` crea detalle
- `GET /api/v1/config-arqueo-detalle/{id}` detalle
- `PUT /api/v1/config-arqueo-detalle/{id}` actualiza detalle
- `DELETE /api/v1/config-arqueo-detalle/{id}` elimina (soft delete)

## Cuerpo (POST/PUT)
- `arqueo_id`: FK `config_arqueo`
- `moneda_id`: FK `config_arqueo_moneda`
- `cantidad`: decimal(2)
- `total`: decimal(2)

## Respuestas
- Éxito: `{ success: true, data: {...}, message: '...' }`
- Error validación: `422`
- No encontrado: `404`
