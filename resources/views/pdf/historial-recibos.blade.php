<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Recibos - {{ $user->primer_nombre }} {{ $user->primer_apellido }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .student-info {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f5f5f5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background-color: #eee;
        }
    </style>
</head>

<body>
    <div class="student-info">
        <strong>ALUMNO:</strong> {{ $user->primer_nombre }} {{ $user->segundo_nombre }} {{ $user->primer_apellido }} {{ $user->segundo_apellido }}<br>
        <strong>DNI/COD:</strong> {{ $user->username }} | <strong>PERÍODO:</strong> {{ $fecha_inicio ?? 'N/A' }} al {{ $fecha_fin ?? 'Hoy' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">FECHA</th>
                <th style="width: 15%;">NÚMERO</th>
                <th style="width: 15%;">TIPO</th>
                <th style="width: 33%;">CONCEPTO(S)</th>
                <th style="width: 12%;" class="text-right">TOTAL</th>
                <th style="width: 13%;" class="text-center">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGeneral = 0; @endphp
            @foreach($recibos as $recibo)
            @php if($recibo->estado !== 'anulado') $totalGeneral += (float)$recibo->total; @endphp
            <tr>
                <td class="text-center">{{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</td>
                <td class="text-center">{{ $recibo->numero_recibo }}</td>
                <td class="text-center">{{ strtoupper($recibo->tipo) }}</td>
                <td>
                    @foreach($recibo->detalles as $detalle)
                    • {{ $detalle->concepto }}<br>
                    @endforeach
                </td>
                <td class="text-right">{{ number_format($recibo->total, 2) }}</td>
                <td class="text-center">{{ strtoupper($recibo->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL (Activos):</td>
                <td class="text-right">{{ number_format($totalGeneral, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>