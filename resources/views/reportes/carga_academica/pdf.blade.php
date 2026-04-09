<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
    <title>{{ $titulo }}</title>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Asignatura</th>
                <th style="width: 25%;">Docente</th>
                <th style="width: 20%;">Grado</th>
                <th style="width: 20%;">Grupo</th>
                <th style="width: 10%;">Periodo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
            <tr>
                <td>{{ $row['asignatura'] }}</td>
                <td>{{ $row['docente'] }}</td>
                <td>{{ $row['grado'] }}</td>
                <td>{{ $row['grupo'] }}</td>
                <td class="text-center">{{ $row['periodo'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>