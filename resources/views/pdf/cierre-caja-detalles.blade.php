<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cierre de Caja - Detalles</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
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
            padding: 5px;
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

        .anulado {
            text-decoration: line-through;
            color: #999;
        }

        .total-row {
            font-weight: bold;
            background-color: #eee;
        }

        tr,
        td,
        th {
            page-break-inside: avoid !important;
        }



        tfoot {
            display: table-footer-group;
        }
    </style>
</head>

<body>
    <table>
        <tbody>
            <tr>
                <th style="width: 10%;">FECHA</th>
                <th style="width: 8%;">NÚMERO</th>
                <th style="width: 8%;">TIPO</th>
                <th style="width: 20%;">USUARIO</th>
                <th style="width: 20%;">CONCEPTO</th>
                <th style="width: 6%;" class="text-center">CANT.</th>
                <th style="width: 10%;" class="text-right">PRECIO</th>
                <th style="width: 8%;" class="text-right">DESC.</th>
                <th style="width: 10%;" class="text-right">SUBTOTAL</th>
            </tr>
            @php($sum = 0)
            @foreach($detalles as $row)
            <tr class="{{ !empty($row['anulado']) ? 'anulado' : '' }}">
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ $row['fecha'] }}</div>
                </td>
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ $row['numero_recibo'] }}</div>
                </td>
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ strtoupper($row['tipo']) }}</div>
                </td>
                <td>
                    <div style="page-break-inside: avoid;">{{ $row['nombre_usuario'] }}</div>
                </td>
                <td>
                    <div style="page-break-inside: avoid;">{{ $row['concepto'] }}</div>
                </td>
                <td class="text-center">
                    <div style="page-break-inside: avoid;">{{ $row['cantidad'] }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($row['monto'], 2) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($row['descuento'], 2) }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($row['total'], 2) }}</div>
                </td>
            </tr>
            @php($sum += (!empty($row['anulado']) ? 0 : (float)$row['total']))
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format($sum, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>