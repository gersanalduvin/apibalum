# Reporte: Arqueo de Caja

## Permiso requerido

- `arqueo_caja` (aplicado por middleware en todas las rutas del módulo)

## Endpoints

- GET `api/v1/reportes/arqueo-caja/resumen`
  - Permiso: `arqueo_caja`
  - Parámetros (query):
    - `fecha` (`Y-m-d`, opcional) — filtra por una fecha específica de recibo
    - `desde` (`Y-m-d`, opcional) — inicio de rango de fecha
    - `hasta` (`Y-m-d`, opcional) — fin de rango de fecha
    - Nota: usar `fecha` o el rango `desde`/`hasta`. Si se provee `fecha` se ignora el rango.
  - Funcionalidad: suma `monto` en `recibos_forma_pago` agrupado por `forma_pago_id` y `nombre` de `config_formas_pago`, filtrando por `recibos.fecha`.
  - Respuesta (200):
    ```json
    {
      "success": true,
      "data": {
        "detalles": [
          { "forma_pago_id": 1, "nombre": "Efectivo Córdobas", "total": 1250.50 },
          { "forma_pago_id": 2, "nombre": "Efectivo Dólares", "total": 100.00 },
          { "forma_pago_id": 3, "nombre": "Tarjeta", "total": 320.00 }
        ],
        "total_general": 1670.50
      },
      "message": "Resumen obtenido"
    }
    ```

- GET `api/v1/reportes/arqueo-caja/monedas`
  - Permiso: `arqueo_caja`
  - Funcionalidad: lista `config_arqueo_moneda` separadas en `cordoba` (`moneda = false`) y `dolar` (`moneda = true`), ordenadas por `orden`.
  - Respuesta (200):
    ```json
    {
      "success": true,
      "data": {
        "cordoba": [
          { "id": 1, "denominacion": "C$ 10", "multiplicador": 10, "orden": 1, "moneda": false },
          { "id": 2, "denominacion": "C$ 20", "multiplicador": 20, "orden": 2, "moneda": false }
        ],
        "dolar": [
          { "id": 5, "denominacion": "$ 1", "multiplicador": 1, "orden": 1, "moneda": true },
          { "id": 6, "denominacion": "$ 5", "multiplicador": 5, "orden": 2, "moneda": true }
        ]
      },
      "message": "Configuración de monedas"
    }
    ```

- POST `api/v1/reportes/arqueo-caja/guardar`
  - Permiso: `arqueo_caja`
  - Body (JSON):
    ```json
    {
      "fecha": "2025-12-03",
      "tasacambio": 36.50,
      "detalles": [
        { "moneda_id": 1, "cantidad": 10 },
        { "moneda_id": 5, "cantidad": 3 }
      ]
    }
    ```
  - Validación:
    - `fecha` requerido (`date`)
    - `tasacambio` requerido (`numeric`)
    - `detalles` requerido (`array|min:1`)
    - `detalles.*.moneda_id` requerido (`exists:config_arqueo_moneda,id`)
    - `detalles.*.cantidad` requerido (`numeric|min:0`)
  - Cálculo y guardado:
    - `total` por detalle = `cantidad * multiplicador` de la denominación (`config_arqueo_moneda`)
    - `totalc` = suma de detalles con `moneda = false`
    - `totald` = suma de detalles con `moneda = true`
    - `totalarqueo` = `totalc + (totald * tasacambio)`
    - Transaccional: se crea `config_arqueo` y sus `config_arqueo_detalle`
  - Respuesta (201):
    ```json
    {
      "success": true,
      "data": {
        "arqueo": {
          "id": 12,
          "fecha": "2025-12-03",
          "totalc": 200.0,
          "totald": 8.0,
          "tasacambio": 36.50,
          "totalarqueo": 492.0,
          "detalles": [
            { "id": 101, "arqueo_id": 12, "moneda_id": 1, "cantidad": 10, "total": 100.0 },
            { "id": 102, "arqueo_id": 12, "moneda_id": 5, "cantidad": 3, "total": 3.0 }
          ]
        }
      },
      "message": "Arqueo guardado"
    }
    ```

- GET `api/v1/reportes/arqueo-caja/detalles/{id}/pdf`
  - Permiso: `arqueo_caja`
  - Parámetros de ruta:
    - `id` (int) — identificador del registro de `config_arqueo`
  - Salida: archivo PDF descargable con:
    - Resumen por Formas de Pago (nombre y total) calculado por la fecha del arqueo
    - Secciones de Córdobas y Dólares, mostrando denominaciones, multiplicadores, cantidades, totales
    - Resumen final: `totalc`, `totald`, `totalarqueo`

## Ejemplos cURL

- Resumen por fecha:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/reportes/arqueo-caja/resumen?fecha=2025-12-03"
  ```

- Resumen por rango:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/reportes/arqueo-caja/resumen?desde=2025-12-01&hasta=2025-12-03"
  ```

- Monedas:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       "http://localhost/api/v1/reportes/arqueo-caja/monedas"
  ```

- Guardar arqueo:
  ```bash
  curl -X POST -H "Authorization: Bearer <token>" \
       -H "Content-Type: application/json" \
       -d '{
             "fecha":"2025-12-03",
             "tasacambio":36.50,
             "detalles":[{"moneda_id":1,"cantidad":10},{"moneda_id":5,"cantidad":3}]
           }' \
       "http://localhost/api/v1/reportes/arqueo-caja/guardar"
  ```

- Imprimir detalles en PDF:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       -o arqueo_detalles.pdf \
       "http://localhost/api/v1/reportes/arqueo-caja/detalles/12/pdf"
  ```

## Notas

- Todas las rutas están bajo `auth:sanctum` (`routes/api.php`) y luego el permiso `arqueo_caja`.
- El resumen agrupa por `forma_pago_id` y nombre de `config_formas_pago` en `recibos_forma_pago`, filtrando por `recibos.fecha`.
- `config_arqueo_moneda.moneda`: `false` = Córdobas, `true` = Dólares.
- Auditoría y campos offline se manejan automáticamente por los modelos `ConfigArqueo`, `ConfigArqueoDetalle` y `ConfigArqueoMoneda`.

## Referencias de Código

- Controlador: `app/Http/Controllers/Api/V1/ReporteArqueoCajaController.php`
- Servicio: `app/Services/ArqueoCajaService.php`
- Rutas: `routes/api/v1/reporte_arqueo_caja.php`
- Permisos: `app/Services/PermissionService.php:322`
