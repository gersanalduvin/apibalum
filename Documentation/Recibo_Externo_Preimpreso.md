# Recibo Externo en Formato Preimpreso

## Objetivo
Imprimir únicamente los datos del recibo externo sobre el papel preimpreso, sin encabezados ni tablas, alineando cada campo en su posición correspondiente.

## Dimensiones de referencia
- Imagen de muestra: 1024x638 px (ratio ≈ 1.604)
- Papel configurado: `letter` vertical (8.5" x 11")
- Márgenes: 5 mm por lado (ajustables)

> Nota: La relación de aspecto del escaneo no coincide con `letter`. Por eso se usan **coordenadas relativas (%)** y márgenes ajustables para calibrar en la impresora real.

## Configuración
- Archivo: `config/recibo_externo_layout.php`
- Campos con coordenadas relativas (`x`, `y`) en porcentaje respecto al área imprimible.
- Fuente y tamaño ajustables.

### Ejemplo de campos
```
'fields' => [
  'numero_recibo' => ['x' => 60, 'y' => 16],
  'fecha_dia' => ['x' => 11, 'y' => 22],
  'fecha_mes' => ['x' => 17, 'y' => 22],
  'fecha_anio' => ['x' => 23, 'y' => 22],
  'recibi_de' => ['x' => 13, 'y' => 36],
  'por_cordoba' => ['x' => 67, 'y' => 36],
  'por_dolar' => ['x' => 82, 'y' => 36],
  'cantidad_letras' => ['x' => 22, 'y' => 42],
  'concepto' => ['x' => 22, 'y' => 48],
  'efectivo_checkbox' => ['x' => 13, 'y' => 69],
  'cheque_checkbox' => ['x' => 24, 'y' => 69],
  'cheque_numero' => ['x' => 33, 'y' => 69],
  'banco' => ['x' => 62, 'y' => 69],
  'firma' => ['x' => 56, 'y' => 85],
]
```

## Render
- Vista: `resources/views/pdf/recibo-externo.blade.php`
- Usa `position: absolute` con `%` para `left`/`top`.
- No se imprime encabezado ni pie de página.

## Calibración sugerida
1. Imprimir un recibo de prueba con datos visibles.
2. Comparar contra el preimpreso y ajustar:
   - Márgenes (`margin_top/right/bottom/left` en mm)
   - Posiciones (`x`/`y` en `%`) en `config/recibo_externo_layout.php`
3. Repetir hasta alinear.

## Campos impresos
- Número de recibo
- Fecha (día, mes, año)
- Recibí de (nombre usuario)
- POR C$ y US (monto total en C$ y conversión a US)
- La cantidad en letras (C$ … /100)
- Concepto (concatenación de conceptos de detalles)
- Efectivo / Cheque (cuadro marcado)
- Cheque No y Banco (dejados en blanco para llenado manual)
- Firma

## Consideraciones
- La conversión a US usa `total / tasa_cambio`.
- Si el recibo tiene formas de pago mixtas, el marcador de efectivo usa la primera forma (`es_efectivo`).
- Se puede crear una página de calibración adicional si se requiere una grilla.
