# Reporte: Cierre de Caja

## Endpoints

- GET `api/v1/reportes/cierre-caja/detalles`
  - Permiso: `reporte_cierre_caja.ver`
  - Parámetros: `tipo` (`todos|interno|externo`), `fecha_inicio` (`Y-m-d`), `fecha_fin` (`Y-m-d`)
  - Respuesta: lista con `fecha` (`dd/mm/yyyy`), `numero_recibo`, `tipo`, `nombre_usuario`, `concepto`, `total`

- GET `api/v1/reportes/cierre-caja/conceptos`
  - Permiso: `reporte_cierre_caja.ver`
  - Parámetros: `tipo` (`todos|interno|externo`), `fecha_inicio`, `fecha_fin`
  - Respuesta: lista agrupada por `concepto` con `count`, `sum_total`

- GET `api/v1/reportes/cierre-caja/detalles/pdf`
  - Permiso: `reporte_cierre_caja.exportar_pdf`
  - Parámetros: `tipo`, `fecha_inicio`, `fecha_fin`

- GET `api/v1/reportes/cierre-caja/conceptos/pdf`
  - Permiso: `reporte_cierre_caja.exportar_pdf`
  - Parámetros: `tipo`, `fecha_inicio`, `fecha_fin`

- GET `api/v1/reportes/cierre-caja/detalles/excel`
  - Permiso: `reporte_cierre_caja.exportar_excel`
  - Parámetros: `tipo`, `fecha_inicio`, `fecha_fin`

- GET `api/v1/reportes/cierre-caja/conceptos/excel`
  - Permiso: `reporte_cierre_caja.exportar_excel`
  - Parámetros: `tipo`, `fecha_inicio`, `fecha_fin`

## Notas

- Se excluyen recibos con `estado = anulado` cuando aplica.
- Fechas se filtran por `recibos.fecha` en rango inclusivo.
- PDF generado con `SnappyPdf` y Excel con `SimpleXlsxGenerator`.
