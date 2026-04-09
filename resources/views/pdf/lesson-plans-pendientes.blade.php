<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            color: #fff;
        }

        .badge-danger {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <div style="margin-bottom: 20px; padding: 10px; background-color: #fff3f3; border-left: 5px solid #dc3545;">
        <strong>Nota:</strong> Este reporte muestra los docentes que tienen asignaciones pero NO han registrado su plan de clase para los filtros seleccionados.
    </div>
    <table>
        <thead>
            <tr>
                <th width="30%">Docente</th>
                <th width="35%">Asignatura</th>
                <th width="20%">Grupo</th>
                <th width="15%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @if(count($items) > 0)
            @foreach($items as $item)
            <tr>
                <td>{{ $item['docente_nombre'] }}</td>
                <td>{{ $item['asignatura_nombre'] }}</td>
                <td>{{ $item['grupo_nombre'] }}</td>
                <td class="text-center">
                    <span class="badge badge-danger">Pendiente</span>
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="4" class="text-center">¡No hay planes pendientes! Todos los docentes han cumplido.</td>
            </tr>
            @endif
        </tbody>
    </table>
</body>

</html>