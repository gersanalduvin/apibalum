<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Actividades por Semana</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            padding: 0;
            font-size: 16px;
        }

        .header p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        /* Rotación para cabeceras si se desea ahorrar espacio */
        .ver-header {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            height: 80px;
            vertical-align: bottom;
            padding-top: 5px;
        }

        .zero-val {
            color: #d32f2f;
        }

        .has-val {
            color: #1976d2;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Reporte de Cantidad de Actividades por Semana</h2>
        <p>Visualice el número de tareas y evidencias diarias planificadas por docente y semana</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 25%">Asignatura</th>
                <th class="text-left" style="width: 20%">Docente</th>
                @foreach($semanas as $sem)
                <th class="ver-header">{{ current(explode(" - ", str_replace("Sem ", "", $sem['rango']))) }}<br>a<br>{{ last(explode(" - ", str_replace("Sem ", "", $sem['rango']))) }}</th>
                @endforeach
                <th class="ver-header">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lineas as $linea)
            <tr>
                <td class="text-left">{{ $linea['asignatura'] }}</td>
                <td class="text-left">{{ $linea['docente'] }}</td>
                @foreach($semanas as $sem)
                @php
                $val = $linea['totales_por_semana'][$sem['key']] ?? 0;
                @endphp
                <td class="{{ $val == 0 ? 'zero-val' : 'has-val' }}">{{ $val }}</td>
                @endforeach
                <td style="font-weight: bold;">{{ $linea['total_general'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($semanas) + 3 }}" class="text-center">No hay datos para mostrar</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>