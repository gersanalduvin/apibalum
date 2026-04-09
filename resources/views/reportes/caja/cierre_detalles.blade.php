<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - Detalles</title>
    <link rel="stylesheet" href="{{ public_path('css/pdf-global-styles.css') }}">
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .meta-info {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            /* More padding for readability */
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        thead {
            display: table-header-group;
        }

        tr,
        td,
        th {
            page-break-inside: avoid !important;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .tachado {
            text-decoration: line-through;
            color: #999;
        }

        .badge {
            padding: 4px 8px;
            /* Larger badge */
            border-radius: 4px;
            font-size: 11px;
            color: white;
            display: inline-block;
            /* Ensure layout integrity */
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-secondary {
            background-color: #6c757d;
        }
    </style>
</head>

<body>
    @include('pdf.header', ['titulo' => 'REPORTE DE CIERRE DE CAJA - DETALLES'])

    <div class="meta-info">
        <strong>Tipo:</strong> {{ ucfirst($meta['tipo'] ?? 'Todos') }} <br>
        <strong>Desde:</strong> {{ $meta['fecha_inicio'] ?? 'N/A' }}
        <strong>Hasta:</strong> {{ $meta['fecha_fin'] ?? 'N/A' }} <br>
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i A') }}
    </div>

    <table>
        <tbody>
            <tr>
                <th style="width: 10%;">Fecha</th>
                <th style="width: 10%;">N° Recibo</th>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 20%;">Usuario/Cliente</th>
                <th style="width: 20%;">Concepto</th>
                <th class="text-center" style="width: 6%;">Cant.</th>
                <th class="text-right" style="width: 8%;">Precio</th>
                <th class="text-right" style="width: 8%;">Desc.</th>
                <th class="text-right" style="width: 8%;">Subtotal</th>
                <th style="width: 8%;">Estado</th>
                <th class="text-right" style="width: 8%;">Total Recibo</th>
            </tr>
            @foreach($groupedDetalles as $group)
            @foreach($group as $index => $detalle)
            <tr class="{{ $detalle->estado == 'anulado' ? 'tachado' : '' }}">
                @if($index === 0)
                <td rowspan="{{ $group->count() }}" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;">{{ $detalle->fecha ? $detalle->fecha->format('d/m/Y') : 'N/A' }}</div>
                </td>
                <td rowspan="{{ $group->count() }}" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;">{{ $detalle->numero_recibo }}</div>
                </td>
                <td rowspan="{{ $group->count() }}" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;">{{ ucfirst($detalle->tipo) }}</div>
                </td>
                <td rowspan="{{ $group->count() }}" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;">{{ $detalle->nombre_usuario }}</div>
                </td>
                @endif

                <td>
                    <div style="page-break-inside: avoid;">{{ $detalle->concepto }}</div>
                </td>
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ number_format($detalle->cantidad, 0) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($detalle->monto, 2) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($detalle->descuento, 2) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($detalle->subtotal, 2) }}</div>
                </td>

                @if($index === 0)
                <td rowspan="{{ $group->count() }}" class="text-center" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;">
                        <span class="badge {{ $detalle->estado == 'activo' ? 'badge-success' : 'badge-danger' }} {{ $detalle->estado == 'anulado' ? '' : '' }}">
                            {{ ucfirst($detalle->estado) }}
                        </span>
                    </div>
                </td>
                <td rowspan="{{ $group->count() }}" class="text-right" style="vertical-align: middle;">
                    <div style="page-break-inside: avoid;"><strong>{{ number_format($detalle->total_recibo, 2) }}</strong></div>
                </td>
                @endif
            </tr>
            @endforeach
            @endforeach
            <tr>
                <td colspan="9" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
            </tr>
        </tbody>
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
