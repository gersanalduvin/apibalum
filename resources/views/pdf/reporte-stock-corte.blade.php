<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Stock</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        .header-cell {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <td class="header-cell">Código</td>
                <td class="header-cell">Producto</td>
                <td class="header-cell" style="width: 80px;">Mínimo</td>
                <td class="header-cell" style="width: 80px;">Máximo</td>
                <td class="header-cell" style="width: 100px;">Stock Actual</td>
                <td class="header-cell" style="width: 100px;">Costo</td>
                <td class="header-cell" style="width: 90px;">Últ. Mov.</td>
            </tr>
        </thead>
        <tbody>
            @php
                $totalMinimo = 0;
                $totalMaximo = 0;
                $totalStock = 0;
                $totalCosto = 0;
            @endphp
            @foreach($productos as $producto)
            @php
                $totalMinimo += $producto->stock_minimo;
                $totalMaximo += $producto->stock_maximo;
                $totalStock += $producto->stock_actual;
                $totalCosto += $producto->costo;
            @endphp
            <tr>
                <td>{{ $producto->codigo }}</td>
                <td>{{ $producto->nombre }}</td>
                <td class="text-right">{{ number_format($producto->stock_minimo, 2) }}</td>
                <td class="text-right">{{ number_format($producto->stock_maximo, 2) }}</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($producto->stock_actual, 2) }}</td>
                <td class="text-right">{{ number_format($producto->costo, 2) }}</td>
                <td class="text-center">{{ $producto->ultima_fecha ? \Carbon\Carbon::parse($producto->ultima_fecha)->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="2" class="text-right">TOTALES:</td>
                <td class="text-right">{{ number_format($totalMinimo, 2) }}</td>
                <td class="text-right">{{ number_format($totalMaximo, 2) }}</td>
                <td class="text-right">{{ number_format($totalStock, 2) }}</td>
                <td class="text-right">{{ number_format($totalCosto, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
