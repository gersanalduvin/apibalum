<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .semestre { background: #eef; font-weight: bold; }
    </style>
    <title>Configuración - Cortes</title>
    </head>
<body>
    @foreach ($semestres as $sem)
        <table>
            <thead>
                <tr class="semestre">
                    <th colspan="6">Semestre: {{ $sem->nombre }} ({{ $sem->abreviatura }}) | Orden: {{ $sem->orden }} | Periodo: {{ optional($sem->periodoLectivo)->nombre }}</th>
                </tr>
                <tr>
                    <th>Parcial</th>
                    <th>Abrev</th>
                    <th>Inicio Corte</th>
                    <th>Fin Corte</th>
                    <th>Inicio Publicación</th>
                    <th>Fin Publicación</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sem->parciales as $p)
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->abreviatura }}</td>
                        <td>{{ optional($p->fecha_inicio_corte)->format('d/m/Y') }}</td>
                        <td>{{ optional($p->fecha_fin_corte)->format('d/m/Y') }}</td>
                        <td>{{ optional($p->fecha_inicio_publicacion_notas)->format('d/m/Y') }}</td>
                        <td>{{ optional($p->fecha_fin_publicacion_notas)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Sin parciales</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>

