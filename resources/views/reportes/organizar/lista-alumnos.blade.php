<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lista de Alumnos</title>
    <style>
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th, .tabla td { border: 1px solid #333; padding: 6px; font-size: 12px; }
        .encabezado { margin-bottom: 10px; font-size: 12px; }
    </style>
    <link rel="stylesheet" href="{{ public_path('css/pdf-global-styles.css') }}">
</head>
<body>
    <div class="encabezado">
        <strong>Período:</strong> {{ $periodo->nombre ?? '' }}
        &nbsp;&nbsp; <strong>Grado:</strong> {{ $grado->nombre ?? '' }}
        &nbsp;&nbsp; <strong>Turno:</strong> {{ $turno->nombre ?? '' }}
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre Completo</th>
                <th>Sexo</th>
                <th>Grupo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $index => $alumno)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $alumno['nombre_completo'] }}</td>
                    <td>{{ $alumno['sexo'] }}</td>
                    <td>{{ $alumno['grupo'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <table class="tabla">
        <thead>
            <tr>
                <th colspan="3">Resumen por sexo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Masculino</td>
                <td>{{ $sexo_masculino }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Femenino</td>
                <td>{{ $sexo_femenino }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Total</td>
                <td>{{ $total_alumnos }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>