<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Alumnos Retirados</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9pt;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8pt;
            color: white;
        }

        .badge-retiro {
            background-color: #dc3545;
        }

        .badge-retiro_anticipado {
            background-color: #fd7e14;
        }
    </style>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">#</th>
                <th width="35%">Nombre Completo</th>
                <th width="15%">Grado/Sección</th>
                <th width="10%">Turno</th>
                <th width="10%">Estado</th>
                <th width="10%" class="text-center">Fecha</th>
                <th width="10%">Obs.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumnos as $index => $alumno)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $alumno->nombre_completo }}</td>
                <td>{{ $alumno->grado_nombre }} - {{ $alumno->seccion_nombre }}</td>
                <td>{{ $alumno->turno_nombre }}</td>
                <td>
                    <span class="badge badge-{{ $alumno->estado }}">
                        {{ ucfirst(str_replace('_', ' ', $alumno->estado)) }}
                    </span>
                </td>
                <td class="text-center">{{ $alumno->fecha_retiro }}</td>
                <td>{{ $alumno->observaciones }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No hay alumnos retirados en este período.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>