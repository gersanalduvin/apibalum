<table>
    <thead>
        <tr>
            <th><strong>Alumno</strong></th>
            <th><strong>GRUPO</strong></th>
            @foreach($meses_cols as $col)
            <th><strong>{{ strtoupper($col) }}</strong></th>
            @endforeach
            <th><strong>Total</strong></th>
        </tr>
    </thead>
    <tbody>
        @foreach($grupos as $g)
        @foreach($g['rows'] as $row)
        <tr>
            <td>{{ $row['alumno'] }}</td>
            <td>{{ $g['grupo_nombre'] ?: 'Grupo' }}</td>
            @foreach($meses_cols as $col)
            @if(is_numeric($row[$col]))
            <td data-format="0.00">{{ $row[$col] }}</td>
            @else
            <td>{{ $row[$col] }}</td>
            @endif
            @endforeach
            @if(is_numeric($row['total']))
            <td data-format="0.00">{{ $row['total'] }}</td>
            @else
            <td>{{ $row['total'] }}</td>
            @endif
        </tr>
        @endforeach
        @endforeach
        <tr>
            <td colspan="2"><strong>Totales</strong></td>
            @foreach($meses_cols as $col)
            @if(is_numeric($resumen_global['totales_por_mes'][$col] ?? ''))
            <td data-format="0.00"><strong>{{ $resumen_global['totales_por_mes'][$col] }}</strong></td>
            @else
            <td><strong>{{ $resumen_global['totales_por_mes'][$col] ?? '' }}</strong></td>
            @endif
            @endforeach
            @if(is_numeric($resumen_global['total_general'] ?? ''))
            <td data-format="0.00"><strong>{{ $resumen_global['total_general'] }}</strong></td>
            @else
            <td><strong>{{ $resumen_global['total_general'] ?? '' }}</strong></td>
            @endif
        </tr>
    </tbody>
</table>
