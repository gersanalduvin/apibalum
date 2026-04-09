<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - Conceptos</title>
    <link rel="stylesheet" href="{{ public_path('css/pdf-global-styles.css') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .meta-info {
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        tr,
        td,
        th {
            page-break-inside: avoid !important;
        }
    </style>
</head>

<body>
    @include('pdf.header', ['titulo' => 'REPORTE DE CIERRE DE CAJA - CONCEPTOS'])

    <div class="meta-info">
        <strong>Tipo:</strong> {{ ucfirst($meta['tipo'] ?? 'Todos') }} <br>
        <strong>Desde:</strong> {{ $meta['fecha_inicio'] ?? 'N/A' }}
        <strong>Hasta:</strong> {{ $meta['fecha_fin'] ?? 'N/A' }} <br>
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i A') }}
    </div>

    <table>
        <tbody>
            <tr>
                <th>Concepto</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
            </tr>
            @foreach($conceptos as $item)
            <tr>
                <td>
                    <div style="page-break-inside: avoid;">{{ $item->concepto }}</div>
                </td>
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ number_format($item->cantidad, 0) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($item->monto, 2) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($item->total, 2) }}</div>
                </td>
            </tr>
            @endforeach
            <tr>
                <td class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-center"><strong>{{ number_format($conceptos->sum('cantidad'), 0) }}</strong></td>
                <td class="text-right"><strong>—</strong></td>
                <td class="text-right"><strong>{{ number_format($conceptos->sum('total'), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <br><br>

    @if($resumenCategorizado)
    <div style="page-break-inside: avoid;">
        <h4 style="margin-bottom: 5px;">Resumen Categorizado</h4>
        <table style="width: 50%;">
            <thead>
                <tr>
                    <th style="background-color: #fce5cd;">Categoría</th>
                    <th class="text-right" style="background-color: #fce5cd;">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mensualidades anticipadas</td>
                    <td class="text-right">{{ number_format($resumenCategorizado['anticipado'], 2) }}</td>
                </tr>
                <tr>
                    <td>Mensualidades del mes en curso</td>
                    <td class="text-right">{{ number_format($resumenCategorizado['en_curso'], 2) }}</td>
                </tr>
                <tr>
                    <td>Mensualidades atrasadas</td>
                    <td class="text-right">{{ number_format($resumenCategorizado['atrasado'], 2) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total cierre de caja</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($resumenCategorizado['total_cierre'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</body>

</html>
