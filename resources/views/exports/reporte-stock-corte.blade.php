<table>
    <thead>
    <tr>
        <th colspan="6" style="font-size: 14pt; font-weight: bold; text-align: center;">REPORTE DE STOCK</th>
    </tr>
    <tr>
        <th colspan="6" style="font-size: 12pt; text-align: center;">Inventario a fecha de corte: {{ \Carbon\Carbon::parse($fechaCorte)->format('d/m/Y') }}</th>
    </tr>
    @if($categoriaNombre)
    <tr>
        <th colspan="6" style="font-size: 11pt; text-align: center;">Categoría: {{ $categoriaNombre }}</th>
    </tr>
    @endif
    <tr></tr>
    <tr>
        <th style="background-color: #D3D3D3; font-weight: bold;">Código</th>
        <th style="background-color: #D3D3D3; font-weight: bold;">Producto</th>
        <th style="background-color: #D3D3D3; font-weight: bold; text-align: right;">Mínimo</th>
        <th style="background-color: #D3D3D3; font-weight: bold; text-align: right;">Máximo</th>
        <th style="background-color: #D3D3D3; font-weight: bold; text-align: right;">Stock Actual</th>
        <th style="background-color: #D3D3D3; font-weight: bold; text-align: right;">Costo</th>
        <th style="background-color: #D3D3D3; font-weight: bold; text-align: right;">Últ. Mov.</th>
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
            <td style="text-align: right;">{{ $producto->stock_minimo }}</td>
            <td style="text-align: right;">{{ $producto->stock_maximo }}</td>
            <td style="text-align: right; font-weight: bold;">{{ $producto->stock_actual }}</td>
            <td style="text-align: right;">{{ $producto->costo }}</td>
            <td style="text-align: right;">{{ $producto->ultima_fecha ? \Carbon\Carbon::parse($producto->ultima_fecha)->format('d/m/Y') : '' }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2" style="font-weight: bold; text-align: right;">TOTALES:</td>
        <td style="font-weight: bold; text-align: right;">{{ $totalMinimo }}</td>
        <td style="font-weight: bold; text-align: right;">{{ $totalMaximo }}</td>
        <td style="font-weight: bold; text-align: right;">{{ $totalStock }}</td>
        <td style="font-weight: bold; text-align: right;">{{ $totalCosto }}</td>
        <td></td>
    </tr>
    </tbody>
</table>
