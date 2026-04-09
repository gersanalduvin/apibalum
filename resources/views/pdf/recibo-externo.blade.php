<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Recibo Externo</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;

            font-family: 'Courier New', Courier, monospace !important;
        }

        .canvas {
            position: relative;
        }

        .field {
            position: absolute;

            font-size: {
                    {
                    data_get(config('recibo_externo_layout'), 'font.size', 11)
                }
            }

            px;
            line-height: 1.15;
        }

        .nowrap {
            white-space: nowrap;
        }

        .multiline {
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body>
    @php($cfg = config('recibo_externo_layout') ?? [])
    @php($page = $cfg['page'] ?? ['margin_left'=>5,'margin_right'=>5,'margin_top'=>5,'margin_bottom'=>5])
    @php($pageWmm = 216)
    @php($pageHmm = 279)
    @php($contentWmm = $pageWmm - (($page['margin_left'] ?? 5) + ($page['margin_right'] ?? 5)))
    @php($contentHmm = $pageHmm - (($page['margin_top'] ?? 5) + ($page['margin_bottom'] ?? 5)))
    <div class="canvas" style="width: {{ $contentWmm }}mm; height: {{ $contentHmm }}mm;">
        @php($fields = ($cfg['fields'] ?? [
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
        ]))
        @php($toPct = fn($n) => $n . '%')

        <div class="field nowrap" style="left: {{ $toPct($fields['numero_recibo']['x']) }}; top: {{ $toPct($fields['numero_recibo']['y']) }};">{{ $recibo->numero_recibo }}</div>
        <div class="field nowrap" style="left: {{ $toPct($fields['fecha_dia']['x']) }}; top: {{ $toPct($fields['fecha_dia']['y']) }};">{{ optional($recibo->fecha)->format('d') }}</div>
        <div class="field nowrap" style="left: {{ $toPct($fields['fecha_mes']['x']) }}; top: {{ $toPct($fields['fecha_mes']['y']) }};">{{ optional($recibo->fecha)->format('m') }}</div>
        <div class="field nowrap" style="left: {{ $toPct($fields['fecha_anio']['x']) }}; top: {{ $toPct($fields['fecha_anio']['y']) }};">{{ optional($recibo->fecha)->format('Y') }}</div>

        <div class="field multiline" style="left: {{ $toPct($fields['recibi_de']['x']) }}; top: {{ $toPct($fields['recibi_de']['y']) }}; width: {{ $contentWmm * 0.55 }}mm;">{{ $recibo->nombre_usuario }}</div>
        <div class="field nowrap" style="left: {{ $toPct($fields['por_cordoba']['x']) }}; top: {{ $toPct($fields['por_cordoba']['y']) }};">{{ number_format($recibo->total, 2) }}</div>


        <div class="field multiline" style="left: {{ $toPct($fields['cantidad_letras']['x']) }}; top: {{ $toPct($fields['cantidad_letras']['y']) }}; width: {{ $contentWmm * 0.60 }}mm;"> {{ $cantidad_letras }}</div>

        <div class="field multiline" style="left: {{ $toPct($fields['concepto']['x']) }}; top: {{ $toPct($fields['concepto']['y']) }}; width: {{ $contentWmm * 0.70 }}mm;">
            {{ $recibo->detalles->map(fn($d) => '(' . (float)$d->cantidad . ') ' . $d->concepto)->implode(', ') }}

            @php($totalDesc = $recibo->detalles->sum('descuento'))
            @if($totalDesc > 0)
            <br><br>
            Desc: {{ number_format($totalDesc, 2) }}
            @endif
        </div>

        @php($isEfectivo = ($recibo->formasPago->first()?->formaPago?->es_efectivo ?? false))
        <div class="field" style="left: {{ $toPct($fields['efectivo_checkbox']['x']) }}; top: {{ $toPct($fields['efectivo_checkbox']['y']) }};">{!! $isEfectivo ? 'X' : ' ' !!}</div>

    </div>
</body>

</html>