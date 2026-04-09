# Módulo: Buscar Recibos

## Permiso requerido

- `buscar_recibo` (aplicado por middleware en todas las rutas del módulo)

## Endpoints

- GET `api/v1/buscar-recibos`
  - Permiso: `buscar_recibo`
  - Parámetros (query):
    - `numero_recibo` (`string`, opcional) — filtro parcial por número de recibo
    - `nombre_usuario` (`string`, opcional) — filtro parcial por nombre del usuario
    - `fecha_inicio` (`Y-m-d`, opcional) — inicio de rango de fecha
    - `fecha_fin` (`Y-m-d`, opcional) — fin de rango de fecha
    - `per_page` (`int`, opcional, por defecto `15`, rango `1..100`)
  - Columnas devueltas: `id`, `fecha`, `nombre_usuario`, `numero_recibo`, `total`
  - Respuesta (200):
    ```json
    {
      "success": true,
      "data": {
        "current_page": 1,
        "data": [
          { "id": 15, "fecha": "2025-12-03", "nombre_usuario": "Juan Pérez", "numero_recibo": "REC-0015", "total": 1250.5 },
          { "id": 14, "fecha": "2025-12-02", "nombre_usuario": "María López", "numero_recibo": "REC-0014", "total": 320.0 }
        ],
        "per_page": 15,
        "total": 2
      },
      "message": "Recibos buscados exitosamente"
    }
    ```

- PUT `api/v1/buscar-recibos/{id}/anular`
  - Permiso: `buscar_recibo`
  - Parámetros de ruta:
    - `id` (int) — identificador del recibo
  - Funcionalidad: anula el recibo y revierte inventario de productos según detalles registrados
  - Respuesta (200):
    ```json
    {
      "success": true,
      "data": {
        "id": 15,
        "numero_recibo": "REC-0015",
        "estado": "anulado",
        "fecha": "2025-12-03",
        "total": 1250.5
      },
      "message": "Recibo anulado exitosamente"
    }
    ```

- GET `api/v1/buscar-recibos/{id}/imprimir`
  - Permiso: `buscar_recibo`
  - Parámetros de ruta:
    - `id` (int) — identificador del recibo
  - Salida: archivo PDF descargable usando el formato existente (interno/externo)

## Ejemplos cURL

- Listar por número de recibo:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/buscar-recibos?numero_recibo=REC-0015&per_page=10"
  ```

- Listar por nombre de usuario:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/buscar-recibos?nombre_usuario=Juan"
  ```

- Listar por rango de fechas:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/buscar-recibos?fecha_inicio=2025-12-01&fecha_fin=2025-12-03"
  ```

- Anular un recibo:
  ```bash
  curl -X PUT -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/buscar-recibos/15/anular"
  ```

- Imprimir un recibo en PDF:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       -o recibo_15.pdf \
       "http://localhost/api/v1/buscar-recibos/15/imprimir"
  ```

## Notas

- Todas las rutas están bajo `auth:sanctum` y luego el permiso `buscar_recibo`.
- La impresión utiliza las vistas y configuración de PDF existentes según el tipo de recibo (`interno`/`externo`).
- El listado devuelve paginación estándar de Laravel con solo las columnas necesarias para búsqueda.

## Referencias de Código

- Controlador: `app/Http/Controllers/Api/V1/BuscarReciboController.php:19` (listar), `app/Http/Controllers/Api/V1/BuscarReciboController.php:39` (anular), `app/Http/Controllers/Api/V1/BuscarReciboController.php:50` (imprimir)
- Servicio (búsqueda): `app/Services/BuscarReciboService.php:12`
- Servicio (anulación): `app/Services/ReciboService.php:144`
- Servicio (imprimir PDF): `app/Services/ReciboService.php:197`
- Rutas: `routes/api/v1/buscar_recibos.php`

