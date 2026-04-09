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

        .badge-success {
            background-color: #28a745;
        }

        .badge-info {
            background-color: #17a2b8;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-secondary {
            background-color: #6c757d;
        }
    </style>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th width="20%">Docente</th>
                <th width="25%">Asignatura</th>
                <th width="20%">Grupo</th>
                <th width="15%">Estado</th>
                <th width="20%">Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['docente'] }}</td>
                <td>{{ $item['asignatura'] }}</td>
                <td>{{ $item['grupo'] }}</td>
                <td class="text-center">
                    @if($item['planificado'])
                    @if($item['enviado'])
                    <span class="badge badge-success">Enviado</span>
                    @else
                    <span class="badge badge-info">Borrador</span>
                    @endif
                    @else
                    <span class="badge badge-danger">Sin Planificar</span>
                    @endif
                </td>
                <td>
                    @if($item['fecha_plan'])
                    Fecha: {{ \Carbon\Carbon::parse($item['fecha_plan'])->format('d/m/Y') }}
                    @else
                    Pendiente de entrega
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>