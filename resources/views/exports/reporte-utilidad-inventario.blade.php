<table>
    <thead>
        <tr>
            <th colspan="8" style="font-weight: bold; font-size: 16px; text-align: center;">
                Reporte de Utilidades de Inventario
            </th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">
                {{ $reporte['periodo']['descripcion'] }}
            </th>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #eeeeee;">Código</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Producto</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Categoría</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Costo Promedio</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Precio Venta</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Vendida</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Costo Venta</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Ingreso Real</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Ganancia</th>
            <th style="font-weight: bold; background-color: #eeeeee;">Margen %</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reporte['productos'] as $producto)
        <tr>
            <td>{{ $producto['codigo'] }}</td>
            <td>{{ $producto['producto'] }}</td>
            <td>{{ $producto['categoria'] }}</td>
            <td>{{ $producto['costo_promedio'] }}</td>
            <td>{{ $producto['precio_venta'] }}</td>
            <td>{{ $producto['cantidad'] }}</td>
            <td>{{ $producto['total_costo'] }}</td>
            <td>{{ $producto['total_venta_potencial'] }}</td>
            <td>{{ $producto['total_ganancia'] }}</td>
            <td>{{ $producto['margen_porcentaje'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5"></td>
            <td style="font-weight: bold;">{{ $reporte['resumen']['total_unidades'] }}</td>
            <td style="font-weight: bold;">{{ $reporte['resumen']['valor_inventario_costo'] }}</td>
            <td style="font-weight: bold;">{{ $reporte['resumen']['valor_inventario_venta'] }}</td>
            <td style="font-weight: bold;">{{ $reporte['resumen']['ganancia_potencial'] }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>