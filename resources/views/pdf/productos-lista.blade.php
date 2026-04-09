<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Listado de Productos</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }

        /* User requested td instead of th for headers to fix cut issues */
        .header-cell {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    {{-- Header is now handled by PDF option, so we can remove this or keep it. 
         But since we added header-html option, this inline header might be redundant or overlapping.
         However, for the first page passing, let's keep it simple. The user asked for table fix.
         Deleting inline header since we are using header-html now. --}}

    <table>
        <thead>
            <tr>
                <td class="header-cell">Código</td>
                <td class="header-cell">Producto</td>
                <td class="header-cell" style="text-align: right">Mínimo</td>
                <td class="header-cell" style="text-align: right">Máximo</td>
                <td class="header-cell" style="text-align: right">Stock Actual</td>
                <td class="header-cell" style="text-align: right">Precio Venta</td>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto)
            <tr>
                <td>{{ $producto->codigo }}</td>
                <td>{{ $producto->nombre }}</td>
                <td style="text-align: right">{{ number_format($producto->stock_minimo, 2) }}</td>
                <td style="text-align: right">{{ number_format($producto->stock_maximo, 2) }}</td>
                <td style="text-align: right">{{ number_format($producto->stock_actual, 2) }}</td>
                <td style="text-align: right">{{ number_format($producto->precio_venta, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>