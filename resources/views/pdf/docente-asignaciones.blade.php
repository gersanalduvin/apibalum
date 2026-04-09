<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Carga Académica</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .header-info {
            width: 100%;
            border: 1px solid #000;
            margin-bottom: 15px;
            padding: 5px;
        }

        .header-info td {
            border: none;
            padding: 2px 5px;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .no-border {
            border: none !important;
        }
    </style>
</head>

<body>
    <table class="header-info">
        <tr>
            <td width="15%" class="font-bold">DOCENTE:</td>
            <td width="85%">{{ strtoupper($docente->nombre_completo) }}</td>
        </tr>
        <tr>
            <td class="font-bold">PERÍODO:</td>
            <td>{{ $periodoNombre ?? 'N/A' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="35%">ASIGNATURA</th>
                <th width="20%">GRADO</th>
                <th width="20%">SECCIÓN</th>
                <th width="20%">TURNO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asignaciones as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $item->asignaturaGrado->materia->nombre ?? $item->asignaturaGrado->asignatura->nombre ?? 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $item->grupo->grado->nombre ?? 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $item->grupo->seccion->nombre ?? 'N/A' }}
                </td>
                <td class="text-center">
                    {{ $item->grupo->turno->nombre ?? 'N/A' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">NO HAY ASIGNATURAS REGISTRADAS PARA ESTE PERÍODO</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>