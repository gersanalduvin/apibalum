<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .titulo { background: #eef; font-weight: bold; }
    </style>
    <title>Asignaturas por Grado</title>
    </head>
<body>
    @foreach ($items as $it)
        <table>
            <thead>
                <tr class="titulo">
                    <th colspan="6">Periodo: {{ optional($it->periodoLectivo)->nombre }} | Grado: {{ optional($it->grado)->nombre }} | Materia: {{ optional($it->materia)->nombre }} | Escala: {{ optional($it->escala)->nombre }} | Orden: {{ $it->orden }}</th>
                </tr>
                <tr>
                    <th>Corte</th>
                    <th>Nota aprobar</th>
                    <th>Nota máxima</th>
                    <th>Promedio</th>
                    <th>MINED</th>
                    <th>Evidencias</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($it->cortes as $c)
                    <tr>
                        <td>{{ optional($c->corte)->nombre }}</td>
                        <td>{{ $it->nota_aprobar }}</td>
                        <td>{{ $it->nota_maxima }}</td>
                        <td>{{ $it->incluir_en_promedio ? 'Sí' : 'No' }}</td>
                        <td>{{ $it->incluir_en_reporte_mined ? 'Sí' : 'No' }}</td>
                        <td>
                            @if(count($c->evidencias))
                                <ul>
                                    @foreach ($c->evidencias as $e)
                                        <li>{{ $e->evidencia }}</li>
                                    @endforeach
                                </ul>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Sin cortes asociados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>

