<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .meta {
            margin-bottom: 10px;
        }

        .right {
            text-align: right;
        }
    </style>
    <title>Arqueo de Caja - Detalles</title>
</head>

<body>
    @php
    $logoPath = public_path('logopp.jpg');
    $logoDataUri = null;
    if (file_exists($logoPath)) {
    try {
    $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    } catch (\Throwable $e) { $logoDataUri = null; }
    }
    @endphp
    <table style="width: 100%; border: none; margin-bottom: 20px;">
        <tr style="border: none;">
            <td style="width: 20%; border: none; text-align: center;">
                @if($logoDataUri)
                <img src="{{ $logoDataUri }}" width="80" height="auto">
                @endif
            </td>
            <td style="width: 60%; border: none; text-align: center;">
                <div style="font-weight: bold; font-size: 16px;">{{ config('app.nombre_institucion', 'INSTITUCIÓN') }}</div>
                <div style="font-weight: bold; font-size: 14px;">ARQUEO DE CAJA - DETALLES</div>
                <div style="font-size: 12px;">
                    Fecha: {{ !empty($data['fecha']) ? \Carbon\Carbon::parse($data['fecha'])->format('d/m/Y') : '' }}
                    <br>
                    Generado: {{ now()->format('d/m/Y H:i') }}
                    <br>
                    Tasa Cambio: {{ number_format($data['tasacambio'], 2) }}
                </div>
            </td>
            <td style="width: 20%; border: none;">&nbsp;</td>
        </tr>
    </table>

    <h3>Resumen por Formas de Pago</h3>
    <table>
        <thead>
            <tr>
                <th>Forma de Pago</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php($sumFP = 0)
            @foreach(($resumenFormasPago['detalles'] ?? []) as $row)
            <tr>
                <td>{{ $row->nombre }}</td>
                <td class="right">{{ number_format($row->total, 2) }}</td>
            </tr>
            @php($sumFP += (float)$row->total)
            @endforeach
            <tr>
                <td class="right"><strong>Total General</strong></td>
                <td class="right"><strong>{{ number_format($sumFP, 2) }}</strong></td>
            </tr>
            <tr>
                <td class="right" style="border-top: 2px solid #ccc;"><strong>Total General Efectivo (C$)</strong></td>
                <td class="right" style="border-top: 2px solid #ccc;"><strong>{{ number_format($totalEfectivo ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @php($cordoba = collect($data['detalles'])->filter(fn($d) => !($d['es_dolar'])))
    @php($dolar = collect($data['detalles'])->filter(fn($d) => $d['es_dolar']))

    <h3>Córdobas</h3>
    <table>
        <thead>
            <tr>
                <th>Denominación</th>
                <th class="right">Multiplicador</th>
                <th class="right">Cantidad</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php($sumC = 0)
            @foreach($cordoba as $row)
            <tr>
                <td>{{ $row['denominacion'] }}</td>
                <td class="right">{{ number_format($row['multiplicador'], 2) }}</td>
                <td class="right">{{ number_format($row['cantidad'], 2) }}</td>
                <td class="right">{{ number_format($row['total'], 2) }}</td>
            </tr>
            @php($sumC += (float)$row['total'])
            @endforeach
            <tr>
                <td colspan="3" class="right"><strong>Total Córdobas</strong></td>
                <td class="right"><strong>{{ number_format($sumC, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h3>Dólares</h3>
    <table>
        <thead>
            <tr>
                <th>Denominación</th>
                <th class="right">Multiplicador</th>
                <th class="right">Cantidad</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php($sumD = 0)
            @foreach($dolar as $row)
            <tr>
                <td>{{ $row['denominacion'] }}</td>
                <td class="right">{{ number_format($row['multiplicador'], 2) }}</td>
                <td class="right">{{ number_format($row['cantidad'], 2) }}</td>
                <td class="right">{{ number_format($row['total'], 2) }}</td>
            </tr>
            @php($sumD += (float)$row['total'])
            @endforeach
            <tr>
                <td colspan="3" class="right"><strong>Total Dólares</strong></td>
                <td class="right"><strong>{{ number_format($sumD, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr>
                <td class="right"><strong>Total Córdobas</strong></td>
                <td class="right">{{ number_format($data['totalc'], 2) }}</td>
            </tr>
            <tr>
                <td class="right"><strong>Total Dólares</strong></td>
                <td class="right">{{ number_format($data['totald'], 2) }}</td>
            </tr>
            <tr>
                <td class="right"><strong>Total Arqueo (C$)</strong></td>
                <td class="right"><strong>{{ number_format($data['totalarqueo'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>

</html>