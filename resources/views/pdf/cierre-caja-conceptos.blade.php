<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cierre de Caja - Por Concepto</title>
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
                <th style="width: 60%;">CONCEPTO</th>
                <th style="width: 20%;" class="text-right">CANTIDAD</th>
                <th style="width: 20%;" class="text-right">SUMA TOTAL</th>
            </tr>
            @php($sum = 0)
            @foreach($conceptos as $row)
            <tr>
                <td>
                    <div style="page-break-inside: avoid;">{{ $row['concepto'] }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ (int)$row['count'] }}</div>
                </td>
                <td class="text-right">
                    <div style="page-break-inside: avoid;">{{ number_format($row['sum_total'], 2) }}</div>
                </td>
            </tr>
            @php($sum += (float)$row['sum_total'])
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format($sum, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>