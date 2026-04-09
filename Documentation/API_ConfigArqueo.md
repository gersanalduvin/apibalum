# API: Configuración de Arqueo

## Permiso requerido
- Todas las rutas protegidas por: `arqueo_caja`

## Objetivo
- Registrar arqueos de caja por fecha, con totales en córdobas (`totalc`), dólares (`totald`), tasa de cambio y total del arqueo.

## Endpoints
- `GET /api/v1/config-arqueo/` lista paginada por `fecha desc`
- `GET /api/v1/config-arqueo/getall` lista completa
- `POST /api/v1/config-arqueo/` crea arqueo
- `GET /api/v1/config-arqueo/{id}` detalle de arqueo (incluye `detalles`)
- `PUT /api/v1/config-arqueo/{id}` actualiza arqueo
- `DELETE /api/v1/config-arqueo/{id}` elimina arqueo (soft delete)

## Cuerpo (POST/PUT)
- `fecha`: date
- `totalc`: decimal(2)
- `totald`: decimal(2)
- `tasacambio`: decimal(2)
- `totalarqueo`: decimal(2)

## Respuestas
- Éxito: `{ success: true, data: {...}, message: '...' }`
- Error validación: `422`
- No encontrado: `404`
