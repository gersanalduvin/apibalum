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
                <th width="20%">Grupos</th>
                <th width="15%">Fecha Clase</th>
                <th width="10%">Estado</th>
                <th width="10%">Creado el</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->user->primer_nombre ?? '' }} {{ $item->user->primer_apellido ?? '' }}</td>
                <td>
                    @if($item->is_general)
                    PLAN GENERAL
                    @else
                    {{ $item->asignatura->materia->nombre ?? $item->asignatura->asignatura->nombre ?? 'N/A' }}
                    @endif
                </td>
                <td>
                    @if($item->groups && count($item->groups) > 0)
                    {{ implode(', ', array_column($item->groups->toArray(), 'nombre')) }}
                    @else
                    Sin Grupos
                    @endif
                </td>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }}
                </td>
                <td class="text-center">
                    @if($item->is_submitted)
                    <span class="badge badge-success">Recibido</span>
                    @else
                    <span class="badge badge-secondary">Borrador</span>
                    @endif
                </td>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>