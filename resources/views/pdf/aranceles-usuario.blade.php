<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Aranceles - {{ $user->primer_nombre }} {{ $user->primer_apellido }}</title>
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

        .totals-table {
            float: right;
            width: 250px;
            margin-top: 15px;
        }

        .totals-table td {
            border: 1px solid #000;
        }

        .grand-total {
            font-weight: bold;
            background-color: #eee;
        }
    </style>
</head>

<body>
    <div class="student-info">
        <strong>ALUMNO:</strong> {{ $user->primer_nombre }} {{ $user->segundo_nombre }} {{ $user->primer_apellido }} {{ $user->segundo_apellido }}<br>
        <strong>CÓDIGO ÚNICO:</strong> {{ $user->codigo_unico ?? 'N/D' }} | <strong>CÓDIGO MINED:</strong> {{ $user->codigo_mined ?? 'N/D' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30%;">RUBRO</th>
                <th style="width: 12%;" class="text-right">IMPORTE</th>
                <th style="width: 10%;" class="text-right">BECA</th>
                <th style="width: 12%;" class="text-right">DESCUENTO</th>
                <th style="width: 12%;" class="text-right">RECARGO</th>
                <th style="width: 12%;" class="text-right">TOTAL</th>
                <th style="width: 12%;" class="text-right">SALDO ACTUAL</th>
            </tr>
        </thead>
        <tbody>
            @php
            $totalImporte = 0; $totalBeca = 0; $totalDescuento = 0; $totalRecargo = 0; $totalFinal = 0; $totalSaldo = 0;
            @endphp
            @foreach($aranceles as $arancel)
            @php
            $totalImporte += (float)$arancel->importe;
            $totalBeca += (float)$arancel->beca;
            $totalDescuento += (float)$arancel->descuento;
            $totalRecargo += (float)$arancel->recargo;
            $totalFinal += (float)$arancel->importe_total;
            $totalSaldo += (float)$arancel->saldo_actual;
            @endphp
            <tr>
                <td>
                    <strong>{{ $arancel->rubro->nombre ?? 'S/N' }}</strong>
                    @if($arancel->arancel)
                    <br><small>{{ $arancel->arancel->nombre }}</small>
                    @endif
                </td>
                <td class="text-right">C$ {{ number_format($arancel->importe, 2) }}</td>
                <td class="text-right">C$ {{ number_format($arancel->beca, 2) }}</td>
                <td class="text-right">C$ {{ number_format($arancel->descuento, 2) }}</td>
                <td class="text-right">C$ {{ number_format($arancel->recargo, 2) }}</td>
                <td class="text-right">C$ {{ number_format($arancel->importe_total, 2) }}</td>
                <td class="text-right"><strong>C$ {{ number_format($arancel->saldo_actual, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="text-right">Subtotal:</td>
            <td class="text-right">C$ {{ number_format($totalImporte, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Beca:</td>
            <td class="text-right">C$ {{ number_format($totalBeca, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Descuento:</td>
            <td class="text-right">C$ {{ number_format($totalDescuento, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Recargo:</td>
            <td class="text-right">C$ {{ number_format($totalRecargo, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="text-right">TOTAL CARGOS:</td>
            <td class="text-right">C$ {{ number_format($totalFinal, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="text-right">SALDO PENDIENTE:</td>
            <td class="text-right">C$ {{ number_format($totalSaldo, 2) }}</td>
        </tr>
    </table>
</body>

</html>