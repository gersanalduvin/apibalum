<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .escala { font-weight: bold; background: #fafafa; }
    </style>
    <title>Escala de Notas</title>
    </head>
<body>
    <h3>Escala de Notas</h3>
    @if(!empty($filters['notas']))
        <p><strong>Filtro:</strong> {{ $filters['notas'] }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Escala</th>
                <th>Nota</th>
                <th>Abreviatura</th>
                <th>Rango Inicio</th>
                <th>Rango Fin</th>
                <th>Orden</th>
            </tr>
        </thead>
        <tbody>
        @foreach($escalas as $escala)
            @php($escalaNombre = $escala->nombre ?? '')
            @forelse($escala->detalles as $d)
                <tr>
                    <td class="escala">{{ $escalaNombre }}</td>
                    <td>{{ $d->nombre ?? '' }}</td>
                    <td>{{ $d->abreviatura ?? '' }}</td>
                    <td>{{ $d->rango_inicio ?? 0 }}</td>
                    <td>{{ $d->rango_fin ?? 0 }}</td>
                    <td>{{ $d->orden ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td class="escala">{{ $escalaNombre }}</td>
                    <td colspan="5">Sin detalles</td>
                </tr>
            @endforelse
        @endforeach
        </tbody>
    </table>
</body>
</html>

