<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Utilidad de Inventario</title>
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

        td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }

        .header-cell {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ddd;
            padding: 4px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            padding: 0;
        }

        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
        }

        tfoot tr td {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .summary-box {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .summary-item {
            display: inline-block;
            margin-right: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- Resumen -->
    <div class="summary-box">
        <div class="summary-item">Total Productos: {{ number_format($reporte['resumen']['total_productos']) }}</div>
        <div class="summary-item">Total Unidades: {{ number_format($reporte['resumen']['total_unidades'], 2) }}</div>
        <br>
        <div class="summary-item">Valor Costo: {{ number_format($reporte['resumen']['valor_inventario_costo'], 2) }}</div>
        <div class="summary-item">Venta Realizada: {{ number_format($reporte['resumen']['valor_inventario_venta'], 2) }}</div>
        <div class="summary-item">Ganancia Real: {{ number_format($reporte['resumen']['ganancia_potencial'], 2) }}</div>
    </div>

    <table>
        <tbody>
            <tr>
                <td class="header-cell">Código</td>
                <td class="header-cell">Producto</td>
                <td class="header-cell">Categoría</td>
                <td class="header-cell">Costo Prom.</td>
                <td class="header-cell">Precio Venta</td>
                <td class="header-cell">Vendida</td>
                <td class="header-cell">Costo Venta</td>
                <td class="header-cell">Ingreso Real</td>
                <td class="header-cell">Ganancia</td>
                <td class="header-cell">Margen</td>
            </tr>
            @foreach($reporte['productos'] as $producto)
            <tr>
                <td>{{ $producto['codigo'] }}</td>
                <td>{{ $producto['producto'] }}</td>
                <td>{{ $producto['categoria'] }}</td>
                <td class="text-right">{{ number_format($producto['costo_promedio'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['precio_venta'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['cantidad'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['total_costo'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['total_venta_potencial'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['total_ganancia'], 2) }}</td>
                <td class="text-right">{{ number_format($producto['margen_porcentaje'], 2) }}%</td>
            </tr>
            @endforeach
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="5" class="text-right">TOTALES</td>
                <td class="text-right">{{ number_format($reporte['resumen']['total_unidades'], 2) }}</td>
                <td class="text-right">{{ number_format($reporte['resumen']['valor_inventario_costo'], 2) }}</td>
                <td class="text-right">{{ number_format($reporte['resumen']['valor_inventario_venta'], 2) }}</td>
                <td class="text-right">{{ number_format($reporte['resumen']['ganancia_potencial'], 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>